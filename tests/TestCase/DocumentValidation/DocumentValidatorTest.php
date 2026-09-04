<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation;

use App\DocumentValidation\Document;
use App\DocumentValidation\DocumentValidator;
use App\DocumentValidation\Exception\TenantNotFoundException;
use App\DocumentValidation\InMemoryRuleProvider;
use App\Test\TestCase\DocumentValidation\Support\FakeRule;
use PHPUnit\Framework\TestCase;

final class DocumentValidatorTest extends TestCase
{
    private function document(int $tenantId = 1): Document
    {
        return new Document('doc-1', $tenantId, 'body', ['k' => 'v']);
    }

    public function testTenantWithNoRulesIsValid(): void
    {
        $validator = new DocumentValidator(new InMemoryRuleProvider([1 => []]));

        $this->assertTrue($validator->validate($this->document())->isValid);
    }

    public function testUnknownTenantIsAnErrorNotAnEmptyRuleSet(): void
    {
        $validator = new DocumentValidator(new InMemoryRuleProvider([1 => []]));

        $this->expectException(TenantNotFoundException::class);
        $validator->validate($this->document(99));
    }

    public function testErrorsAreMergedInProviderOrderAndNotShortCircuited(): void
    {
        $validator = new DocumentValidator(new InMemoryRuleProvider([
            1 => [new FakeRule(['first']), new FakeRule([]), new FakeRule(['second', 'third'])],
        ]));

        $result = $validator->validate($this->document());

        $this->assertSame(['first', 'second', 'third'], $result->errors);
    }

    public function testThrowingRulePropagates(): void
    {
        $boom = new \RuntimeException('rule bug');
        $validator = new DocumentValidator(new InMemoryRuleProvider([1 => [new FakeRule([], $boom)]]));

        $this->expectExceptionObject($boom);
        $validator->validate($this->document());
    }

    public function testEveryRuleSeesTheSameDocument(): void
    {
        $a = new FakeRule();
        $b = new FakeRule();
        $validator = new DocumentValidator(new InMemoryRuleProvider([1 => [$a, $b]]));
        $doc = $this->document();

        $validator->validate($doc);

        $this->assertSame([$doc], $a->seen);
        $this->assertSame([$doc], $b->seen);
    }

    public function testRunExecutesRulesWithoutTouchingTheProvider(): void
    {
        $validator = new DocumentValidator(new InMemoryRuleProvider([]));

        $result = $validator->run($this->document(), [new FakeRule(['e'])]);

        $this->assertSame(['e'], $result->errors);
    }
}
