<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class DocumentValidationControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use JsonApiTestTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useJsonApi();
    }

    public function testIndexRendersReadOnlyRuleSummaries(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'text/html']]);
        $this->get('/tenants/1/validate');

        $this->assertResponseOk();
        $this->assertResponseContains('Required metadata');
        $this->assertResponseContains('author, title');
    }

    public function testValidDocumentReturns200(): void
    {
        $this->post('/tenants/1/validate', json_encode([
            'documentId' => 'DOC-123',
            'content' => 'An ordinary report.',
            'metadata' => ['author' => 'Ann', 'title' => 'Report'],
        ]));

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['isValid']);
        $this->assertSame([], $body['errors']);
    }

    public function testInvalidDocumentStillReturns200WithErrors(): void
    {
        $this->post('/tenants/1/validate', json_encode([
            'documentId' => 'DOC-123',
            'content' => 'foo',
            'metadata' => ['title' => 'Note'],
        ]));

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['isValid']);
        $this->assertCount(2, $body['errors']);
    }

    public function testEmptyCorpHasNoRulesSoEverythingPasses(): void
    {
        $this->post('/tenants/2/validate', json_encode([
            'documentId' => 'DOC-123',
            'content' => str_repeat('x', 5000)
        ]));

        $this->assertResponseCode(200);
        $this->assertTrue(json_decode((string)$this->_response->getBody(), true)['isValid']);
    }

    public function testUnknownTenantReturns404(): void
    {
        $this->post('/tenants/999/validate', json_encode([
            'documentId' => 'DOC-123',
            'content' => 'An ordinary report.',
        ]));

        $this->assertResponseCode(404);
        $this->assertArrayHasKey('message', json_decode((string)$this->_response->getBody(), true));
    }

    public function testArrayMetadataValueGivesACleanError(): void
    {
        $rules = $this->fetchTable('TenantValidationRules');
        $rules->saveOrFail($rules->newEntity([
            'tenant_id' => 1,
            'type' => 'metadata_allowed_values',
            'config' => ['field' => 'classification', 'values' => ['public']],
        ]));

        $this->post('/tenants/1/validate', json_encode([
            'documentId' => 'DOC-123',
            'content' => 'An ordinary report.',
            'metadata' => ['author' => 'A', 'title' => 'T', 'classification' => ['public']],
        ]));

        $this->assertResponseCode(200);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertContains('Metadata field "classification" must be a single value.', $body['errors']);
    }

    public function testMalformedJsonBodyReturns400Json(): void
    {
        $this->post('/tenants/1/validate', '{ this is not json');

        $this->assertResponseCode(400);
        $this->assertHeaderContains('Content-Type', 'application/json');
        $this->assertArrayHasKey('message', json_decode((string)$this->_response->getBody(), true));
    }

    public function testContentMustBeAStringOtherwise400(): void
    {
        $this->post('/tenants/1/validate', json_encode(['content' => ['not', 'a', 'string']]));
        $this->assertResponseCode(400);
    }
}
