<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 *
 * @method \App\Model\Entity\TenantValidationRule newEmptyEntity()
 * @method \App\Model\Entity\TenantValidationRule get(mixed $primaryKey, array $options = [])
 * @method \App\Model\Entity\TenantValidationRule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\TenantValidationRule newEntity(array $data, array $options = [])
 */
class TenantValidationRulesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tenant_validation_rules');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        // SQLite stores json as TEXT; tell the ORM to encode/decode it.
        $this->getSchema()->setColumnType('config', 'json');

        $this->belongsTo('Tenants', [
            'foreignKey' => 'tenant_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->requirePresence('type', 'create')
            ->scalar('type')
            ->notEmptyString('type')
            ->requirePresence('config', 'create')
            ->add('config', 'isArray', [
                'rule' => static fn ($value): bool => is_array($value),
                'message' => 'Config must be an object.',
            ]);
    }
}
