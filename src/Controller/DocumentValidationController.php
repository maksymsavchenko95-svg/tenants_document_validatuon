<?php
declare(strict_types=1);

namespace App\Controller;

use App\DocumentValidation\Document;
use App\DocumentValidation\DocumentValidator;
use App\DocumentValidation\Exception\TenantNotFoundException;
use App\DocumentValidation\RuleFactory;
use App\Model\Table\TenantsTable;
use App\Model\Table\TenantValidationRulesTable;
use App\Service\RuleSummarizer;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Random\RandomException;

/**
 *
 * @property TenantsTable $Tenants
 * @property TenantValidationRulesTable $TenantValidationRules
 */
class DocumentValidationController extends AppController
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
     * GET /tenants/{tenantId}/validate
     * Renders document validation form for tenant.
     *
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
            throw new NotFoundException(sprintf('Tenant %d not found.', (int)$tenantId));
        }

        $rows = $this->TenantValidationRules->find()
            ->where(['tenant_id' => $tenant->id])
            ->orderBy(['id' => 'ASC'])
            ->all();

        $this->set('initial', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'rules' => array_map(
                fn ($r): string => $summarizer->summarize($factory->create($r->type, $r->config)),
                $rows->toList(),
            ),
        ]);
        $this->viewBuilder()->setTemplatePath('DocumentValidation');
        return $this->render();
    }

    /**
     * validate action.
     * POST /tenants/{tenantId}/validate
     * Validates document.
     *
     * @param string $tenantId
     * @param DocumentValidator $validator
     * @return Response
     * @throws RandomException
     */
    public function validate(string $tenantId, DocumentValidator $validator): Response
    {
        $this->request->allowMethod('post');

        $data = (array)$this->request->getData();

        $documentId = $data['documentId'] ?? null;
        if (!is_string($documentId) || trim($documentId) === '') {
            return $this->json(['message' => '"documentId" must be a non-blank string.'], 400);
        }
        if (mb_strlen($documentId) > 64) {
            return $this->json(['message' => '"documentId" must be at most 64 characters.'], 400);
        }


        $content = $data['content'] ?? '';
        if (!is_string($content)) {
            return $this->json(['message' => '"content" must be a string.'], 400);
        }

        $metadata = $data['metadata'] ?? [];
        if (!is_array($metadata)) {
            return $this->json(['message' => '"metadata" must be an object.'], 400);
        }
        foreach ($metadata as $key => $_) {
            if (!is_string($key)) {
                return $this->json(['message' => '"metadata" keys must be strings.'], 400);
            }
        }

        $document = new Document($documentId, (int)$tenantId, $content, $metadata);

        try {
            $result = $validator->validate($document);
        } catch (TenantNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], 404);
        }

        return $this->json($result->toArray(), 200);
    }
}
