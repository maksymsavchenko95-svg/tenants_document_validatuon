<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * @method \App\Model\Entity\Tenant newEmptyEntity()
 * @method \App\Model\Entity\Tenant get(mixed $primaryKey, array $options = [])
 * @method \App\Model\Entity\Tenant patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class TenantsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tenants');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->hasMany('TenantValidationRules', [
            'foreignKey' => 'tenant_id',
            'sort' => ['TenantValidationRules.id' => 'ASC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->scalar('name')
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->maxLength('name', 255);
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['name']), ['errorField' => 'name', 'message' => 'That tenant name is already taken.']);

        return $rules;
    }

    public function findWithRuleCount(SelectQuery $query): SelectQuery
    {
        return $query
            ->select([
                'id', 'name', 'created',
                'rule_count' => $query->func()->count('TenantValidationRules.id'),
            ])
            ->leftJoinWith('TenantValidationRules')
            ->groupBy(['Tenants.id', 'Tenants.name', 'Tenants.created'])
            ->orderBy(['Tenants.name' => 'ASC']);
    }
}
