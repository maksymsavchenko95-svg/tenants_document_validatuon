<?php
declare(strict_types=1);

namespace App\DocumentValidation;

use App\DocumentValidation\Exception\UnknownRuleTypeException;

/**
 * Turns a `(type string, config array)` pair into a {@see ConfigurableRule}.
 */
final class RuleFactory
{
    /**
     * @var array<string, class-string<ConfigurableRule>>
     */
    private array $byType = [];

    /**
     * @param list<class-string<ConfigurableRule>> $ruleClasses
     */
    public function __construct(array $ruleClasses)
    {
        foreach ($ruleClasses as $class) {
            $this->byType[$class::type()] = $class;
        }
    }

    /**
     * @param array<string, mixed> $config
     * @throws UnknownRuleTypeException
     * @throws Exception\InvalidRuleConfigException
     */
    public function create(string $type, array $config): ConfigurableRule
    {
        $class = $this->byType[$type] ?? throw UnknownRuleTypeException::forType($type, $this->types());

        return $class::fromConfig($config);
    }

    /**
     * @return list<string>
     */
    public function types(): array
    {
        return array_keys($this->byType);
    }
}
