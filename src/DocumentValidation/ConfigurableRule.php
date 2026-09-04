<?php
declare(strict_types=1);

namespace App\DocumentValidation;

use App\DocumentValidation\Exception\InvalidRuleConfigException;

/**
 * A {@see ValidationRule} that can be built from, and serialised back to, a
 * plain configuration array.
 */
interface ConfigurableRule extends ValidationRule
{
    /**
     * Stable machine-readable identifier for the rule type, e.g. "max_size".
     */
    public static function type(): string;

    /**
     * @param array<string, mixed> $config
     * @throws InvalidRuleConfigException
     */
    public static function fromConfig(array $config): static;

    /**
     * @return array<string, mixed> Normalised config; only keys this rule uses.
     */
    public function toConfig(): array;
}
