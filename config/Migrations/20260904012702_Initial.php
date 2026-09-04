<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class Initial extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        //tenants table
        $this->table('tenants')
            ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['name'], ['unique' => true])
            ->create();

        //tenant's rules
        $this->table('tenant_validation_rules')
            ->addColumn('tenant_id', 'integer', ['null' => false])
            ->addColumn('type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('config', 'json', ['null' => false])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addIndex(['tenant_id'])
            ->addForeignKey('tenant_id', 'tenants', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
