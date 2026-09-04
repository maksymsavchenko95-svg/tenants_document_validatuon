<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Rule;

use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Rule\MaxDocumentSizeRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MaxDocumentSizeRuleTest extends TestCase
{
    private function validate(int $maxBytes, string $content): bool
    {
        $rule = MaxDocumentSizeRule::fromConfig(['max_bytes' => $maxBytes]);

        return $rule->validate(new Document('d', 1, $content))->isValid;
    }

    /**
     * @return iterable<string, array{int, string, bool}>
     */
    public static function sizes(): iterable
    {
        yield 'under the limit' => [10, 'abc', true];
        yield 'exactly at the limit' => [3, 'abc', true];
        yield 'one byte over' => [3, 'abcd', false];
        yield 'zero allows only empty content' => [0, '', true];
        yield 'zero rejects one character' => [0, 'a', false];
        yield 'multibyte counts bytes not characters' => [3, 'é', true];   // "é" = 2 bytes
        yield 'multibyte over the byte limit' => [3, ' éé', false];         // 1 + 2 + 2 = 5 bytes
    }

    #[DataProvider('sizes')]
    public function testSizeBoundaries(int $maxBytes, string $content, bool $expectedValid): void
    {
        $this->assertSame($expectedValid, $this->validate($maxBytes, $content));
    }

    public function testMessageStatesBothNumbersAndTheWordBytes(): void
    {
        $result = MaxDocumentSizeRule::fromConfig(['max_bytes' => 2])
            ->validate(new Document('d', 1, 'abcd'));

        $this->assertSame(
            ['Document content is 4 bytes, which exceeds the maximum of 2 bytes.'],
            $result->errors,
        );
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedConfigs(): iterable
    {
        yield 'missing' => [[]];
        yield 'negative' => [['max_bytes' => -1]];
        yield 'string digits' => [['max_bytes' => '10']];
        yield 'string with suffix' => [['max_bytes' => '10abc']];
        yield 'float' => [['max_bytes' => 2.9]];
        yield 'bool' => [['max_bytes' => true]];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('rejectedConfigs')]
    public function testRejectsBadConfig(array $config): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        MaxDocumentSizeRule::fromConfig($config);
    }

    public function testToConfigRoundTrips(): void
    {
        $this->assertSame(['max_bytes' => 42], MaxDocumentSizeRule::fromConfig(['max_bytes' => 42])->toConfig());
    }
}
