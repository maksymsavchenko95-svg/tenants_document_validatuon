<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $type
 * @property array<string, mixed> $config
 * @property \Cake\I18n\DateTime|null $created
 * @property \App\Model\Entity\Tenant $tenant
 */
class TenantValidationRule extends Entity
{
    protected array $_accessible = [
        'tenant_id' => true,
        'type' => true,
        'config' => true,
    ];

    /**
     * Always expose config as an array, whether the row was hydrated from the
     * json column type (decoded) or as a raw JSON string.
     *
     * @return array<string, mixed>
     */
    protected function _getConfig(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }
}
