<?php
declare(strict_types=1);

namespace App\DocumentValidation\Rule;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\ValidationResult;

/**
 * Fails when the value of a metadata field is not one of a configured set.
 */
final class MetadataAllowedValuesRule implements ConfigurableRule
{
    /**
     * @var list<string>
     */
    private array $allowedValues;

    /**
     * @param list<scalar> $allowedValues
     */
    public function __construct(private readonly string $field, array $allowedValues)
    {
        if (trim($this->field) === '') {
            throw InvalidRuleConfigException::because(self::type(), '"field" must be a non-blank string.');
        }
        $normalised = [];
        foreach ($allowedValues as $value) {
            $value = (string)$value;
            $normalised[$value] = $value;
        }
        if ($normalised === []) {
            throw InvalidRuleConfigException::because(self::type(), '"values" must list at least one allowed value.');
        }

        $this->allowedValues = array_values($normalised);
    }

    public static function type(): string
    {
        return 'metadata_allowed_values';
    }

    public static function fromConfig(array $config): static
    {
        if (!array_key_exists('field', $config) || !is_string($config['field'])) {
            throw InvalidRuleConfigException::because(self::type(), '"field" must be a non-blank string.');
        }
        if (!array_key_exists('values', $config) || !is_array($config['values']) || $config['values'] === []) {
            throw InvalidRuleConfigException::because(self::type(), '"values" must be a non-empty list of scalars.');
        }
        foreach ($config['values'] as $value) {
            if (!is_scalar($value)) {
                throw InvalidRuleConfigException::because(self::type(), 'every entry in "values" must be a scalar.');
            }
        }

        return new self($config['field'], array_values($config['values']));
    }

    public function toConfig(): array
    {
        return ['field' => $this->field, 'values' => $this->allowedValues];
    }

    public function validate(Document $document): ValidationResult
    {
        if (!array_key_exists($this->field, $document->metadata)) {
            return ValidationResult::valid();
        }

        $value = $document->metadata[$this->field];
        if (!is_scalar($value)) {
            return ValidationResult::invalid(sprintf(
                'Metadata field "%s" must be a single value.',
                $this->field,
            ));
        }

        if (in_array((string)$value, $this->allowedValues, true)) {
            return ValidationResult::valid();
        }

        return ValidationResult::invalid(sprintf(
            'Metadata field "%s" has value "%s", which is not one of: %s.',
            $this->field,
            (string)$value,
            implode(', ', $this->allowedValues),
        ));
    }
}
