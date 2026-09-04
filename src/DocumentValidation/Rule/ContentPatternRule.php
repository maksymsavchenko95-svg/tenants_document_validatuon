<?php
declare(strict_types=1);

namespace App\DocumentValidation\Rule;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\ValidationResult;

/**
 * Fails when the document content does not match a configured regular expression.
 */
final class ContentPatternRule implements ConfigurableRule
{
    public function __construct(private readonly string $pattern)
    {
        if ($this->pattern === '') {
            throw InvalidRuleConfigException::because(self::type(), '"pattern" must not be empty.');
        }
        if (@preg_match($this->delimited(), '') === false) {
            throw InvalidRuleConfigException::because(
                self::type(),
                sprintf('"%s" is not a valid regular expression.', $this->pattern),
            );
        }
    }

    public static function type(): string
    {
        return 'content_pattern';
    }

    public static function fromConfig(array $config): static
    {
        if (!array_key_exists('pattern', $config) || !is_string($config['pattern'])) {
            throw InvalidRuleConfigException::because(self::type(), '"pattern" must be a string.');
        }

        return new self($config['pattern']);
    }

    public function toConfig(): array
    {
        return ['pattern' => $this->pattern];
    }

    public function validate(Document $document): ValidationResult
    {
        if (preg_match($this->delimited(), $document->content) === 1) {
            return ValidationResult::valid();
        }

        return ValidationResult::invalid(sprintf(
            'Document content does not match the required pattern "%s".',
            $this->pattern,
        ));
    }

    private function delimited(): string
    {
        // Escape only slashes the user has not already escaped, so both "a/b"
        // and "a\/b" compile to the same expression.
        return '/' . preg_replace('~(?<!\\\\)/~', '\\/', $this->pattern) . '/u';
    }
}
