<?php
declare(strict_types=1);

namespace App\DocumentValidation\Exception;

final class UnknownRuleTypeException extends DocumentValidationException
{
    /**
     * @param list<string> $known
     */
    public static function forType(string $type, array $known): self
    {
        return new self(sprintf(
            'Unknown validation rule type "%s". Known types: %s.',
            $type,
            $known === [] ? '(none)' : implode(', ', $known),
        ));
    }
}
