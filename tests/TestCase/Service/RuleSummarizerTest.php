<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\DocumentValidation\Rule\ContentPatternRule;
use App\DocumentValidation\Rule\MaxDocumentSizeRule;
use App\DocumentValidation\Rule\MetadataAllowedValuesRule;
use App\DocumentValidation\Rule\ProhibitedWordsRule;
use App\DocumentValidation\Rule\RequiredMetadataRule;
use App\Service\RuleSummarizer;
use PHPUnit\Framework\TestCase;

final class RuleSummarizerTest extends TestCase
{
    private RuleSummarizer $summarizer;

    protected function setUp(): void
    {
        $this->summarizer = new RuleSummarizer();
    }

    public function testMaxSize(): void
    {
        $this->assertSame(
            'Max size · 10 240 bytes',
            $this->summarizer->summarize(MaxDocumentSizeRule::fromConfig(['max_bytes' => 10240])),
        );
    }

    public function testRequiredMetadata(): void
    {
        $this->assertSame(
            'Required metadata · author, title',
            $this->summarizer->summarize(RequiredMetadataRule::fromConfig(['fields' => ['author', 'title']])),
        );
    }

    public function testProhibitedWords(): void
    {
        $this->assertSame(
            'Prohibited words · foo, bar',
            $this->summarizer->summarize(ProhibitedWordsRule::fromConfig(['words' => ['foo', 'bar']])),
        );
    }

    public function testContentPattern(): void
    {
        $this->assertSame(
            'Content pattern · ^DRAFT',
            $this->summarizer->summarize(ContentPatternRule::fromConfig(['pattern' => '^DRAFT'])),
        );
    }

    public function testMetadataAllowedValues(): void
    {
        $this->assertSame(
            'Metadata allowed values · classification: public, internal',
            $this->summarizer->summarize(MetadataAllowedValuesRule::fromConfig([
                'field' => 'classification',
                'values' => ['public', 'internal'],
            ])),
        );
    }
}
