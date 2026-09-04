<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Rule;

use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Rule\ContentPatternRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContentPatternRuleTest extends TestCase
{
    public function testPassesWhenContentMatches(): void
    {
        $rule = ContentPatternRule::fromConfig(['pattern' => '^DRAFT:']);

        $this->assertSame([], $rule->validate(new Document('d', 1, 'DRAFT: hello'))->errors);
    }

    public function testFailsWhenContentDoesNotMatch(): void
    {
        $rule = ContentPatternRule::fromConfig(['pattern' => '^DRAFT:']);

        $this->assertSame(
            ['Document content does not match the required pattern "^DRAFT:".'],
            $rule->validate(new Document('d', 1, 'hello'))->errors,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function slashPatterns(): iterable
    {
        yield 'bare slash' => ['a/b'];
        yield 'already-escaped slash' => ['a\/b'];
    }

    #[DataProvider('slashPatterns')]
    public function testASlashInThePatternMatchesEitherWayItIsWritten(string $pattern): void
    {
        $rule = ContentPatternRule::fromConfig(['pattern' => $pattern]);

        $this->assertSame([], $rule->validate(new Document('d', 1, 'x a/b y'))->errors);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedConfigs(): iterable
    {
        yield 'missing' => [[]];
        yield 'not a string' => [['pattern' => 123]];
        yield 'empty' => [['pattern' => '']];
        yield 'invalid regex' => [['pattern' => '(unclosed']];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('rejectedConfigs')]
    public function testRejectsBadConfigAtConstruction(array $config): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        ContentPatternRule::fromConfig($config);
    }

    public function testToConfigRoundTrips(): void
    {
        $this->assertSame(['pattern' => '^a.*z$'], ContentPatternRule::fromConfig(['pattern' => '^a.*z$'])->toConfig());
    }
}
