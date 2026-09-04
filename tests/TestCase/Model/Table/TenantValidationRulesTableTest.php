<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\TenantValidationRulesTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class TenantValidationRulesTableTest extends TestCase
{
    use LocatorAwareTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    private TenantValidationRulesTable $Rules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->Rules = $this->fetchTable('TenantValidationRules');
    }

    public function testConfigRoundTripsAsAnArray(): void
    {
        $saved = $this->Rules->saveOrFail($this->Rules->newEntity([
            'tenant_id' => 2,
            'type' => 'metadata_allowed_values',
            'config' => ['field' => 'x', 'values' => ['a', 'b']],
        ]));

        $reloaded = $this->Rules->get($saved->id);
        $this->assertSame(['field' => 'x', 'values' => ['a', 'b']], $reloaded->config);
    }

    public function testTypeIsRequired(): void
    {
        $rule = $this->Rules->newEntity(['tenant_id' => 2, 'config' => []]);

        $this->assertArrayHasKey('type', $rule->getErrors());
    }

    public function testConfigMustBeAnArray(): void
    {
        $rule = $this->Rules->newEntity(['tenant_id' => 2, 'type' => 'max_size', 'config' => 'nope']);

        $this->assertArrayHasKey('config', $rule->getErrors());
    }
}
