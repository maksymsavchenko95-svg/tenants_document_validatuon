<?php
declare(strict_types=1);

namespace App\DocumentValidation;

/**
 * Validates a document against its tenant's rules.
 */
final class DocumentValidator
{
    public function __construct(private readonly ValidationRuleProvider $provider)
    {
    }

    public function validate(Document $document): ValidationResult
    {
        return $this->run($document, $this->provider->rulesFor($document->tenantId));
    }

    /**
     * @param iterable<ValidationRule> $rules Rules to apply, in order.
     */
    public function run(Document $document, iterable $rules): ValidationResult
    {
        $result = ValidationResult::valid();
        foreach ($rules as $rule) {
            $result = $result->merge($rule->validate($document));
        }

        return $result;
    }
}
