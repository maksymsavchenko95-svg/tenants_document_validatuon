<?php
declare(strict_types=1);

namespace App\DocumentValidation\Rule;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\ValidationResult;

/**
 * Fails when the document content is larger than a configured maximum.
 */
final class MaxDocumentSizeRule implements ConfigurableRule
{
    public function __construct(private readonly int $maxBytes)
    {
        if ($this->maxBytes < 0) {
            throw InvalidRuleConfigException::because(self::type(), '"max_bytes" must be zero or greater.');
        }
    }

    public static function type(): string
    {
        return 'max_size';
    }

    public static function fromConfig(array $config): static
    {
        if (!array_key_exists('max_bytes', $config)) {
            throw InvalidRuleConfigException::because(self::type(), '"max_bytes" is required.');
        }
        if (!is_int($config['max_bytes'])) {
            throw InvalidRuleConfigException::because(self::type(), '"max_bytes" must be an integer.');
        }

        return new self($config['max_bytes']);
    }

    public function toConfig(): array
    {
        return ['max_bytes' => $this->maxBytes];
    }

    public function validate(Document $document): ValidationResult
    {
        $actual = strlen($document->content);
        if ($actual <= $this->maxBytes) {
            return ValidationResult::valid();
        }

        return ValidationResult::invalid(sprintf(
            'Document content is %d bytes, which exceeds the maximum of %d bytes.',
            $actual,
            $this->maxBytes,
        ));
    }
}
