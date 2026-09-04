<?php
declare(strict_types=1);

namespace App\DocumentValidation;

use App\DocumentValidation\Exception\TenantNotFoundException;

/**
 * A {@see ValidationRuleProvider} backed by a plain map for tests purposers. No database, no I/O.
 */
final class InMemoryRuleProvider implements ValidationRuleProvider
{
    /**
     * @param array<int, list<ValidationRule>> $rulesByTenant
     */
    public function __construct(private readonly array $rulesByTenant)
    {
    }

    public function rulesFor(int $tenantId): array
    {
        if (!array_key_exists($tenantId, $this->rulesByTenant)) {
            throw TenantNotFoundException::forTenant($tenantId);
        }

        return $this->rulesByTenant[$tenantId];
    }
}
