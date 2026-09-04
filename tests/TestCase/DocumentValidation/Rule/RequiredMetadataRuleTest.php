<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Rule;

use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\Rule\RequiredMetadataRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequiredMetadataRuleTest extends TestCase
{
    /**
     * @param array<string, mixed> $metadata
     * @return list<string>
     */
    private function errorsFor(array $fields, array $metadata): array
    {
        return RequiredMetadataRule::fromConfig(['fields' => $fields])
            ->validate(new Document('d', 1, 'body', $metadata))
            ->errors;
    }

    public function testPassesWhenEveryFieldIsPresentAndNonEmpty(): void
    {
        $this->assertSame([], $this->errorsFor(['title', 'author'], ['title' => 'T', 'author' => 'A']));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function absentValues(): iterable
    {
        yield 'key missing' => [[]];
        yield 'null value' => [['title' => null]];
        yield 'empty string' => [['title' => '']];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    #[DataProvider('absentValues')]
    public function testReportsMissingOrEmptyField(array $metadata): void
    {
        $this->assertSame(
            ['Required metadata field "title" is missing or empty.'],
            $this->errorsFor(['title'], $metadata),
        );
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function presentValues(): iterable
    {
        yield 'zero string' => [['title' => '0']];
        yield 'integer zero' => [['title' => 0]];
        yield 'false' => [['title' => false]];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    #[DataProvider('presentValues')]
    public function testZeroAndFalseCountAsPresent(array $metadata): void
    {
        $this->assertSame([], $this->errorsFor(['title'], $metadata));
    }

    public function testOneErrorPerMissingFieldInConfigOrder(): void
    {
        $this->assertSame(
            [
                'Required metadata field "title" is missing or empty.',
                'Required metadata field "author" is missing or empty.',
            ],
            $this->errorsFor(['title', 'author'], []),
        );
    }

    public function testFieldNamesAreTrimmedDeblankedAndDeduplicated(): void
    {
        $rule = RequiredMetadataRule::fromConfig(['fields' => [' title ', 'title', '', '  ']]);

        $this->assertSame(['fields' => ['title']], $rule->toConfig());
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function rejectedConfigs(): iterable
    {
        yield 'missing' => [[]];
        yield 'string not list (D-2)' => [['fields' => 'title']];
        yield 'non-string element' => [['fields' => ['ok', 5]]];
        yield 'empty after trim' => [['fields' => ['   ', '']]];
        yield 'empty list' => [['fields' => []]];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('rejectedConfigs')]
    public function testRejectsBadConfig(array $config): void
    {
        $this->expectException(InvalidRuleConfigException::class);
        RequiredMetadataRule::fromConfig($config);
    }
}
