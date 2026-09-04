<?php
declare(strict_types=1);

namespace App\Service;

use App\DocumentValidation\ConfigurableRule;
use App\DocumentValidation\Rule\ContentPatternRule;
use App\DocumentValidation\Rule\MaxDocumentSizeRule;
use App\DocumentValidation\Rule\MetadataAllowedValuesRule;
use App\DocumentValidation\Rule\ProhibitedWordsRule;
use App\DocumentValidation\Rule\RequiredMetadataRule;

/**
 * Turns a configured rule into a one-line human summary for the UI.
 *
 * Presentation lives here, out of the domain, so the rules page and the
 * read-only list on the validate page show identical text.
 */
final class RuleSummarizer
{
    public function summarize(ConfigurableRule $rule): string
    {
        $config = $rule->toConfig();

        return match ($rule::type()) {
            MaxDocumentSizeRule::type() => sprintf('Max size · %s bytes', number_format((int)$config['max_bytes'], 0, '.', ' ')),
            RequiredMetadataRule::type() => sprintf('Required metadata · %s', implode(', ', $config['fields'])),
            ProhibitedWordsRule::type() => sprintf('Prohibited words · %s', implode(', ', $config['words'])),
            ContentPatternRule::type() => sprintf('Content pattern · %s', $config['pattern']),
            MetadataAllowedValuesRule::type() => sprintf(
                'Metadata allowed values · %s: %s',
                $config['field'],
                implode(', ', $config['values']),
            ),
            default => ucfirst(str_replace('_', ' ', $rule::type())),
        };
    }
}
