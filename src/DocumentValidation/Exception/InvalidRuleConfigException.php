<?php
declare(strict_types=1);

namespace App\DocumentValidation\Exception;

final class InvalidRuleConfigException extends DocumentValidationException
{
    public static function because(string $type, string $reason): self
    {
        return new self(sprintf('Invalid configuration for rule "%s": %s', $type, $reason));
    }
}
