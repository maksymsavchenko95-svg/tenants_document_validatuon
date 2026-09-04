<?php
declare(strict_types=1);

namespace App\DocumentValidation;

use App\DocumentValidation\Exception\TenantNotFoundException;

/**
 * Resolves the set of validation rules that apply to a given tenant.
 */
interface ValidationRuleProvider
{
    /**
     * @return list<ValidationRule> Rules for the tenant, in the order they run.
     * @throws TenantNotFoundException When the tenant does not exist.
     */
    public function rulesFor(int $tenantId): array;
}
