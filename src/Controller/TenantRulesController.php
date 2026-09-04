<?php
declare(strict_types=1);

namespace App\Controller;

use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Exception\UnknownRuleTypeException;
use App\DocumentValidation\RuleFactory;
use App\Model\Table\TenantsTable;
use App\Model\Table\TenantValidationRulesTable;
use App\Service\RuleSummarizer;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 *
 * @property TenantsTable $Tenants
 * @property TenantValidationRulesTable $TenantValidationRules
 */
class TenantRulesController extends AppController
{
    protected TenantsTable $Tenants;
    protected TenantValidationRulesTable $TenantValidationRules;

    public function initialize(): void
    {
        parent::initialize();
        $this->Tenants = $this->fetchTable('Tenants');
        $this->TenantValidationRules = $this->fetchTable('TenantValidationRules');
    }

    /**
     * Index action.
     * GET /tenants/{tenantId}/rules
     * Renders tenant rules CRUD form.
     * @param string $tenantId
     * @param RuleFactory $factory
     * @param RuleSummarizer $summarizer
     * @return void
     */
    public function index(string $tenantId, RuleFactory $factory, RuleSummarizer $summarizer): Response
    {
        try {
            $tenant = $this->Tenants->get((int)$tenantId);
        } catch (RecordNotFoundException) {
            throw new NotFoundException(sprintf('Tenant %d not found.', $tenantId));
        }

        $rows = $this->TenantValidationRules->find()
            ->where(['tenant_id' => $tenant->id])
            ->orderBy(['id' => 'ASC'])
            ->all();

        $this->set('initial', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'rules' => array_map(
                fn ($r): array => $this->present($r, $factory, $summarizer),
                $rows->toList(),
            ),
            'types' => $factory->types(),
        ]);
        $this->viewBuilder()->setTemplatePath('TenantRules');
        return $this->render();

    }

    /**
     * Add action.
     * POST /tenants/{tenantId}/rules
     * Adds new rule to tenant.
     * @param string $tenantId
     * @param RuleFactory $factory
     * @param RuleSummarizer $summarizer
     * @return Response
     */
    public function add(string $tenantId, RuleFactory $factory, RuleSummarizer $summarizer): Response
    {
        $this->request->allowMethod('post');

        if (!$this->Tenants->exists(['id' => (int)$tenantId])) {
            return $this->json(['message' => sprintf('Tenant %d not found.', (int)$tenantId)], 404);
        }

        $data = (array)$this->request->getData();
        $type = is_string($data['type'] ?? null) ? $data['type'] : '';
        $config = (array)($data['config'] ?? []);

        try {
            $rule = $factory->create($type, $config);
        } catch (UnknownRuleTypeException | InvalidRuleConfigException $e) {
            return $this->json(['errors' => ['config' => $e->getMessage()]], 422);
        }

        $entity = $this->TenantValidationRules->newEntity([
            'tenant_id' => (int)$tenantId,
            'type' => $rule::type(),
            'config' => $rule->toConfig(),
        ]);
        if (!$this->TenantValidationRules->save($entity)) {
            return $this->json(['errors' => $this->fieldErrors($entity->getErrors())], 422);
        }

        return $this->json($this->present($this->TenantValidationRules->get($entity->id), $factory, $summarizer), 201);
    }

    /**
     * Delete action.
     * DELETE /tenants/{tenantId}/rules/{ruleId}
     * Deletes rule from tenant.
     * @param string $tenantId
     * @param string $ruleId
     * @return Response
     */
    public function delete(string $tenantId, string $ruleId): Response
    {
        $this->request->allowMethod(['delete', 'post']);

        try {
            $rule = $this->TenantValidationRules->get((int)$ruleId);
        } catch (RecordNotFoundException) {
            return $this->json(['message' => 'Rule not found.'], 404);
        }
        if ($rule->tenant_id !== (int)$tenantId) {
            return $this->json(['message' => 'Rule not found.'], 404);
        }

        $this->TenantValidationRules->delete($rule);

        return $this->response->withStatus(204);
    }

    /**
     * @return array{id: int, type: string, config: array<string, mixed>, summary: string}
     */
    private function present(
        \App\Model\Entity\TenantValidationRule $row,
        RuleFactory $factory,
        RuleSummarizer $summarizer,
    ): array {
        $rule = $factory->create($row->type, $row->config);

        return [
            'id' => $row->id,
            'type' => $row->type,
            'config' => $row->config,
            'summary' => $summarizer->summarize($rule),
        ];
    }
}
