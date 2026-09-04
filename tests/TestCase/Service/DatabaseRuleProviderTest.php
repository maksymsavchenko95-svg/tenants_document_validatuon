<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\DocumentValidation\Exception\TenantNotFoundException;
use App\DocumentValidation\Rule\MaxDocumentSizeRule;
use App\DocumentValidation\Rule\ProhibitedWordsRule;
use App\DocumentValidation\Rule\RequiredMetadataRule;
use App\DocumentValidation\RuleFactory;
use App\Service\DatabaseRuleProvider;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;

final class DatabaseRuleProviderTest extends TestCase
{
    use LocatorAwareTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    private DatabaseRuleProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new DatabaseRuleProvider(
            $this->fetchTable('Tenants'),
            $this->fetchTable('TenantValidationRules'),
            new RuleFactory([MaxDocumentSizeRule::class, RequiredMetadataRule::class, ProhibitedWordsRule::class]),
        );
    }

    public function testReturnsBuiltRuleObjectsInIdOrder(): void
    {
        $rules = $this->provider->rulesFor(1);

        $this->assertCount(3, $rules);
        $this->assertInstanceOf(MaxDocumentSizeRule::class, $rules[0]);
        $this->assertInstanceOf(RequiredMetadataRule::class, $rules[1]);
        $this->assertInstanceOf(ProhibitedWordsRule::class, $rules[2]);
    }

    public function testTenantThatExistsWithNoRulesReturnsEmptyList(): void
    {
        $this->assertSame([], $this->provider->rulesFor(2));
    }

    public function testUnknownTenantThrowsTenantNotFound(): void
    {
        $this->expectException(TenantNotFoundException::class);
        $this->provider->rulesFor(999);
    }
}
