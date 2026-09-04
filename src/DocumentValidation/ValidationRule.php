<?php
declare(strict_types=1);

namespace App\DocumentValidation;

/**
 * A single, self-contained validation constraint applied to a document.
 * Reported through the returned {@see ValidationResult}.
 *
 */
interface ValidationRule
{
    public function validate(Document $document): ValidationResult;
}
