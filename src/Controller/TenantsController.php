<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\TenantsTable;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Response;

/**
 * @property TenantsTable $Tenants
 */
class TenantsController extends AppController
{
    protected TenantsTable $Tenants;

    public function initialize(): void
    {
        parent::initialize();
        $this->Tenants = $this->fetchTable('Tenants');
    }

    /**
     * Index action.
     * GET /tenants
     * Renders tenant list.
     * @return void
     */
    public function index(): Response
    {
        $rows = $this->Tenants->find('withRuleCount')->all();

        $this->set('initial', [
            'tenants' => array_map(
                static fn ($t): array => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'ruleCount' => (int)$t->rule_count,
                    'created' => $t->created?->format(DATE_ATOM),
                ],
                $rows->toList(),
            ),
        ]);
        return $this->render();
    }

    /**
     * Add action.
     * POST /tenants
     * Adds new tenant.
     *
     * @return Response
     */
    public function add(): Response
    {
        $this->request->allowMethod('post');

        $tenant = $this->Tenants->newEmptyEntity();
        $this->Tenants->patchEntity($tenant, (array)$this->request->getData(), ['fields' => ['name']]);

        if (!$this->Tenants->save($tenant)) {
            return $this->json(['errors' => $this->fieldErrors($tenant->getErrors())], 422);
        }

        return $this->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'created' => $tenant->created?->format(DATE_ATOM),
        ], 201);
    }

    /**
     * Delete action.
     * DELETE /tenants/{id}
     * Deletes tenant.
     * @param string $id
     * @return Response
     */
    public function delete(string $id): Response
    {
        $this->request->allowMethod(['delete', 'post']);

        try {
            $tenant = $this->Tenants->get((int)$id);
        } catch (RecordNotFoundException) {
            return $this->json(['message' => 'Tenant not found.'], 404);
        }

        $this->Tenants->delete($tenant);

        return $this->response->withStatus(204);
    }
}
