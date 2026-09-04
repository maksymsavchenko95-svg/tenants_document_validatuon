<?php
declare(strict_types=1);

namespace App\DocumentValidation;

/**
 * Immutable outcome of validating a document.
 */
final readonly class ValidationResult
{
    public bool $isValid;

    /**
     * @param list<string> $errors
     */
    public function __construct(public array $errors = [])
    {
        $this->isValid = $errors === [];
    }

    public static function valid(): self
    {
        return new self();
    }

    public static function invalid(string ...$errors): self
    {
        return new self(array_values($errors));
    }

    public function merge(self $other): self
    {
        return new self([...$this->errors, ...$other->errors]);
    }

    /**
     * @return array{isValid: bool, errors: list<string>}
     */
    public function toArray(): array
    {
        return ['isValid' => $this->isValid, 'errors' => $this->errors];
    }
}
