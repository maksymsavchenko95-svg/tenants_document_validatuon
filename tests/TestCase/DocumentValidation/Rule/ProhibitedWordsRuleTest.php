<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Rule;

use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Rule\ProhibitedWordsRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProhibitedWordsRuleTest extends TestCase
{
    /**
     * @param list<string> $words
     * @return list<string>
     */
    private function errorsFor(array $words, string $content): array
    {
        return ProhibitedWordsRule::fromConfig(['words' => $words])
            ->validate(new Document('d', 1, $content))
            ->errors;
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function matching(): iterable
    {
        yield 'exact word' => ['this is secret stuff', true];
        yield 'different case' => ['SECRET plans', true];
        yield 'trailing punctuation' => ['it is Secret.', true];
        yield 'substring does not match' => ['the secretary called', false];
        yield 'word absent' => ['nothing to see', false];
    }

    #[DataProvider('matching')]
    public function testWholeWordCaseInsensitiveMatching(string $content, bool $shouldFlag): void
    {
        $errors = $this->errorsFor(['secret'], $content);

        $this->assertSame($shouldFlag ? ['Document content contains the prohibited word "secret".'] : [], $errors);
    }

    public function testAssDoesNotMatchClass(): void
    {
        $this->assertSame([], $this->errorsFor(['ass'], 'we attend the class every week'));
        $this->assertSame(['Document content contains the prohibited word "ass".'], $this->errorsFor(['ass'], 'do not be an ass'));
    }

    public function testOneErrorPerWordFound(): void
    {
        $this->assertSame(
            [
                'Document content contains the prohibited word "foo".',
                'Document content contains the prohibited word "bar".',
            ],
            $this->errorsFor(['foo', 'bar', 'qux'], 'foo and bar both appear here, quxx does not'),
        );
    }

    public function testWordsAreTrimmedLowercasedAndDeduplicated(): void
    {
        $rule = ProhibitedWordsRule::fromConfig(['words' => [' Foo ', 'foo', 'FOO', '']]);

        $this->assertSame(['words' => ['foo']], $rule->toConfig());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedConfigs(): iterable
    {
        yield 'missing' => [[]];
        yield 'string not list' => [['words' => 'foo']];
        yield 'non-string element' => [['words' => ['ok', 1]]];
        yield 'blank only (D-4)' => [['words' => ['  ', ' ']]];
        yield 'empty list' => [['words' => []]];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('rejectedConfigs')]
    public function testRejectsBadConfig(array $config): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        ProhibitedWordsRule::fromConfig($config);
    }
}
