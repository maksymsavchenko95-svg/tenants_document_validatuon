<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation\Support;

use App\DocumentValidation\Document;
use App\DocumentValidation\ValidationRule;
use App\DocumentValidation\ValidationResult;

/**
 * A rule whose behaviour the test controls: it records every document it sees
 * and returns a fixed result (or throws, to prove the validator does not
 * swallow exceptions).
 */
final class FakeRule implements ValidationRule
{
    /** @var list<Document> */
    public array $seen = [];

    /**
     * @param list<string> $errors
     */
    public function __construct(
        private readonly array $errors = [],
        private readonly ?\Throwable $throw = null,
    ) {
    }

    public function validate(Document $document): ValidationResult
    {
        $this->seen[] = $document;
        if ($this->throw !== null) {
            throw $this->throw;
        }

        return new ValidationResult($this->errors);
    }
}
