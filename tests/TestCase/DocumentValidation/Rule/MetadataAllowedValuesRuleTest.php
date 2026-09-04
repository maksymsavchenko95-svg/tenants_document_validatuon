<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Rule;

use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Rule\MetadataAllowedValuesRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetadataAllowedValuesRuleTest extends TestCase
{
    /**
     * @param array<string, mixed> $metadata
     * @return list<string>
     */
    private function errorsFor(array $metadata): array
    {
        return MetadataAllowedValuesRule::fromConfig(['field' => 'classification', 'values' => ['public', 'internal']])
            ->validate(new Document('d', 1, 'body', $metadata))
            ->errors;
    }

    public function testSilentWhenFieldAbsent(): void
    {
        $this->assertSame([], $this->errorsFor([]));
    }

    public function testPassesForAnAllowedValue(): void
    {
        $this->assertSame([], $this->errorsFor(['classification' => 'internal']));
    }

    public function testFailsForADisallowedValue(): void
    {
        $this->assertSame(
            ['Metadata field "classification" has value "secret", which is not one of: public, internal.'],
            $this->errorsFor(['classification' => 'secret']),
        );
    }

    public function testArrayValueIsACleanErrorNotAWarning(): void
    {
        $this->assertSame(
            ['Metadata field "classification" must be a single value.'],
            $this->errorsFor(['classification' => ['public']]),
        );
    }

    public function testScalarValuesAreComparedAsStrings(): void
    {
        $rule = MetadataAllowedValuesRule::fromConfig(['field' => 'level', 'values' => [1, 2, 3]]);

        $this->assertSame([], $rule->validate(new Document('d', 1, 'b', ['level' => 1]))->errors);
        $this->assertSame([], $rule->validate(new Document('d', 1, 'b', ['level' => '2']))->errors);
    }

    public function testToConfigIsNormalised(): void
    {
        $rule = MetadataAllowedValuesRule::fromConfig(['field' => 'f', 'values' => ['a', 'a', 'b']]);

        $this->assertSame(['field' => 'f', 'values' => ['a', 'b']], $rule->toConfig());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedConfigs(): iterable
    {
        yield 'field missing' => [['values' => ['a']]];
        yield 'field blank' => [['field' => '  ', 'values' => ['a']]];
        yield 'values missing' => [['field' => 'f']];
        yield 'values empty' => [['field' => 'f', 'values' => []]];
        yield 'values not a list' => [['field' => 'f', 'values' => 'a']];
        yield 'non-scalar value' => [['field' => 'f', 'values' => [['x']]]];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('rejectedConfigs')]
    public function testRejectsBadConfig(array $config): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        MetadataAllowedValuesRule::fromConfig($config);
    }
}
