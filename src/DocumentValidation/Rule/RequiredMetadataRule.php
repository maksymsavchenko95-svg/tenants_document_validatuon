<?php
declare(strict_types=1);

namespace App\DocumentValidation\Rule;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\ValidationResult;

/**
 * Fails when any of a configured set of metadata fields is missing or empty.
 */
final class RequiredMetadataRule implements ConfigurableRule
{
    /**
     * @var list<string> Normalised: trimmed, non-blank, unique.
     */
    private array $fields;

    /**
     * @param list<string> $fields
     */
    public function __construct(array $fields)
    {
        $normalised = [];
        foreach ($fields as $field) {
            $field = trim($field);
            if ($field !== '') {
                $normalised[$field] = $field;
            }
        }
        if ($normalised === []) {
            throw InvalidRuleConfigException::because(self::type(), 'at least one non-blank field name is required.');
        }

        $this->fields = array_values($normalised);
    }

    public static function type(): string
    {
        return 'required_metadata';
    }

    public static function fromConfig(array $config): static
    {
        if (!array_key_exists('fields', $config) || !is_array($config['fields'])) {
            throw InvalidRuleConfigException::because(self::type(), '"fields" must be a list of strings.');
        }
        foreach ($config['fields'] as $field) {
            if (!is_string($field)) {
                throw InvalidRuleConfigException::because(self::type(), 'every entry in "fields" must be a string.');
            }
        }

        return new self(array_values($config['fields']));
    }

    public function toConfig(): array
    {
        return ['fields' => $this->fields];
    }

    public function validate(Document $document): ValidationResult
    {
        $errors = [];
        foreach ($this->fields as $field) {
            $value = $document->metadata[$field] ?? null;
            if ($value === null || $value === '') {
                $errors[] = sprintf('Required metadata field "%s" is missing or empty.', $field);
            }
        }

        return new ValidationResult($errors);
    }
}
