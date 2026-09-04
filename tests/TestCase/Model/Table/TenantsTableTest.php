<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TenantsTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class TenantsTableTest extends TestCase
{
    use LocatorAwareTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    private TenantsTable $Tenants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Tenants = $this->fetchTable('Tenants');
    }

    public function testNameIsTrimmedOnMarshal(): void
    {
        $tenant = $this->Tenants->newEntity(['name' => '   Spaced   ']);
        $this->Tenants->saveOrFail($tenant);

        $this->assertSame('Spaced', $this->Tenants->get($tenant->id)->name);
    }

    public function testBlankNameIsRejected(): void
    {
        $tenant = $this->Tenants->newEntity(['name' => '   ']);

        $this->assertArrayHasKey('name', $tenant->getErrors());
    }

    public function testNameMustBeUnique(): void
    {
        $tenant = $this->Tenants->newEntity(['name' => 'Acme']);
        $this->Tenants->save($tenant);

        $this->assertArrayHasKey('name', $tenant->getErrors());
    }

    public function testDeletingATenantCascadesToItsRules(): void
    {
        $rules = $this->fetchTable('TenantValidationRules');
        $this->assertSame(3, $rules->find()->where(['tenant_id' => 1])->count());

        $this->Tenants->delete($this->Tenants->get(1));

        $this->assertSame(0, $rules->find()->where(['tenant_id' => 1])->count());
    }

    public function testFindWithRuleCount(): void
    {
        $rows = $this->Tenants->find('withRuleCount')->all()->indexBy('name')->toArray();

        $this->assertSame(3, (int)$rows['Acme']->rule_count);
        $this->assertSame(0, (int)$rows['Empty Corp']->rule_count);
    }
}
