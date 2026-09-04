<?php
declare(strict_types=1);

namespace App\DocumentValidation\Exception;

final class TenantNotFoundException extends DocumentValidationException
{
    public static function forTenant(int $tenantId): self
    {
        return new self(sprintf('Tenant %d does not exist.', $tenantId));
    }
}
