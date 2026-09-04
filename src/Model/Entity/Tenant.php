<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\TenantValidationRule[] $tenant_validation_rules
 */
class Tenant extends Entity
{
    protected array $_accessible = [
        'name' => true,
    ];
}
