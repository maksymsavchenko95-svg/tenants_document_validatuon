<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation;

use App\DocumentValidation\Exception\TenantNotFoundException;
use App\DocumentValidation\InMemoryRuleProvider;
use App\Test\TestCase\DocumentValidation\Support\FakeRule;
use PHPUnit\Framework\TestCase;

final class InMemoryRuleProviderTest extends TestCase
{
    public function testReturnsRulesForAKnownTenantInOrder(): void
    {
        $a = new FakeRule();
        $b = new FakeRule();
        $provider = new InMemoryRuleProvider([7 => [$a, $b]]);

        $this->assertSame([$a, $b], $provider->rulesFor(7));
    }

    public function testKnownTenantWithNoRulesReturnsEmptyList(): void
    {
        $provider = new InMemoryRuleProvider([7 => []]);

        $this->assertSame([], $provider->rulesFor(7));
    }

    public function testUnknownTenantThrows(): void
    {
        $provider = new InMemoryRuleProvider([7 => []]);

        $this->expectException(TenantNotFoundException::class);
        $provider->rulesFor(8);
    }
}
