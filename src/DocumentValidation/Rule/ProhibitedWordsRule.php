<?php
declare(strict_types=1);

namespace App\DocumentValidation\Rule;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Document;
use App\DocumentValidation\Exception\InvalidRuleConfigException;
use App\DocumentValidation\ValidationResult;

/**
 * Fails when the document content contains any of a configured set of words.
 */
final class ProhibitedWordsRule implements ConfigurableRule
{
    /**
     * @var list<string> Normalised: trimmed, lower-cased, non-blank, unique.
     */
    private array $words;

    /**
     * @param list<string> $words
     */
    public function __construct(array $words)
    {
        $normalised = [];
        foreach ($words as $word) {
            $word = mb_strtolower(trim($word));
            if ($word !== '') {
                $normalised[$word] = $word;
            }
        }
        if ($normalised === []) {
            throw InvalidRuleConfigException::because(self::type(), 'at least one non-blank word is required.');
        }

        $this->words = array_values($normalised);
    }

    public static function type(): string
    {
        return 'prohibited_words';
    }

    public static function fromConfig(array $config): static
    {
        if (!array_key_exists('words', $config) || !is_array($config['words'])) {
            throw InvalidRuleConfigException::because(self::type(), '"words" must be a list of strings.');
        }
        foreach ($config['words'] as $word) {
            if (!is_string($word)) {
                throw InvalidRuleConfigException::because(self::type(), 'every entry in "words" must be a string.');
            }
        }

        return new self(array_values($config['words']));
    }

    public function toConfig(): array
    {
        return ['words' => $this->words];
    }

    public function validate(Document $document): ValidationResult
    {
        $errors = [];
        foreach ($this->words as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';
            if (preg_match($pattern, $document->content) === 1) {
                $errors[] = sprintf('Document content contains the prohibited word "%s".', $word);
            }
        }

        return new ValidationResult($errors);
    }
}
