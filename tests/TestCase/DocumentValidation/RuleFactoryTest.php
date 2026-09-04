<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Exception\UnknownRuleTypeException;
use App\DocumentValidation\Rule\ContentPatternRule;
use App\DocumentValidation\Rule\MaxDocumentSizeRule;
use App\DocumentValidation\Rule\MetadataAllowedValuesRule;
use App\DocumentValidation\Rule\ProhibitedWordsRule;
use App\DocumentValidation\Rule\RequiredMetadataRule;
use App\DocumentValidation\RuleFactory;
use App\DocumentValidation\ValidationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RuleFactoryTest extends TestCase
{
    private function factory(): RuleFactory
    {
        return new RuleFactory([
            MaxDocumentSizeRule::class,
            RequiredMetadataRule::class,
            ProhibitedWordsRule::class,
            MetadataAllowedValuesRule::class,
            ContentPatternRule::class,
        ]);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, class-string}>
     */
    public static function knownTypes(): iterable
    {
        yield 'max_size' => ['max_size', ['max_bytes' => 10], MaxDocumentSizeRule::class];
        yield 'required_metadata' => ['required_metadata', ['fields' => ['a']], RequiredMetadataRule::class];
        yield 'prohibited_words' => ['prohibited_words', ['words' => ['x']], ProhibitedWordsRule::class];
        yield 'metadata_allowed_values' => ['metadata_allowed_values', ['field' => 'f', 'values' => ['a']], MetadataAllowedValuesRule::class];
        yield 'content_pattern' => ['content_pattern', ['pattern' => '^a'], ContentPatternRule::class];
    }

    /**
     * @param array<string, mixed> $config
     * @param class-string $expected
     */
    #[DataProvider('knownTypes')]
    public function testCreatesEachKnownType(string $type, array $config, string $expected): void
    {
        $this->assertInstanceOf($expected, $this->factory()->create($type, $config));
    }

    public function testUnknownTypeThrows(): void
    {
        $this->expectException(UnknownRuleTypeException::class);
        $this->factory()->create('nope', []);
    }

    public function testInvalidConfigThrows(): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        $this->factory()->create('max_size', ['max_bytes' => -1]);
    }

    public function testTypesListsEveryRegisteredType(): void
    {
        $this->assertSame(
            ['max_size', 'required_metadata', 'prohibited_words', 'metadata_allowed_values', 'content_pattern'],
            $this->factory()->types(),
        );
    }

    public function testAFactoryBuiltWithATestOnlyRuleClassIsTheOnlyExtensionPoint(): void
    {
        $factory = new RuleFactory([TestOnlyRule::class]);

        $rule = $factory->create('test_only', ['note' => 'hi']);

        $this->assertSame(['test_only'], $factory->types());
        $this->assertSame(['note' => 'hi'], $rule->toConfig());
    }

    public function testRoundTripsNormalisedConfig(): void
    {
        $rule = $this->factory()->create('required_metadata', ['fields' => [' Title ', 'title', '']]);

        $this->assertSame(['fields' => ['Title', 'title']], $rule->toConfig());
    }
}

/**
 * Proves the factory needs nothing but a class implementing ConfigurableRule.
 */
final class TestOnlyRule implements ConfigurableRule
{
    public function __construct(private readonly string $note)
    {
    }

    public static function type(): string
    {
        return 'test_only';
    }

    public static function fromConfig(array $config): static
    {
        return new self((string)($config['note'] ?? ''));
    }

    public function toConfig(): array
    {
        return ['note' => $this->note];
    }

    public function validate(Document $document): ValidationResult
    {
        return ValidationResult::valid();
    }
}
