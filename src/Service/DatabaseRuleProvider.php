<?php
declare(strict_types=1);

namespace App\Service;

use App\DocumentValidation\Exception\TenantNotFoundException;
use App\DocumentValidation\RuleFactory;
use App\DocumentValidation\ValidationRuleProvider;
use App\Model\Table\TenantsTable;
use App\Model\Table\TenantValidationRulesTable;

/**
 * The one place the validation flow meets persistence.
 */
final class DatabaseRuleProvider implements ValidationRuleProvider
{
    public function __construct(
        private readonly TenantsTable $tenants,
        private readonly TenantValidationRulesTable $rules,
        private readonly RuleFactory $factory,
    ) {
    }

    public function rulesFor(int $tenantId): array
    {
        if (!$this->tenants->exists(['id' => $tenantId])) {
            throw TenantNotFoundException::forTenant($tenantId);
        }

        $rows = $this->rules->find()
            ->where(['tenant_id' => $tenantId])
            ->orderBy(['id' => 'ASC'])
            ->all();

        return array_map(
            fn ($row) => $this->factory->create($row->type, $row->config),
            $rows->toList(),
        );
    }
}
