<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class TenantRulesControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use JsonApiTestTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useJsonApi();
    }

    public function testIndexRendersRuleSummaries(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'text/html']]);
        $this->get('/tenants/1/rules');

        $this->assertResponseOk();
        $this->assertResponseContains('Max size');
        $this->assertResponseContains('10 240');
    }

    public function testAddPersistsNormalisedConfigAndReturnsSummary(): void
    {
        $this->post('/tenants/2/rules', json_encode([
            'type' => 'prohibited_words',
            'config' => ['words' => [' Foo ', 'foo', 'BAR']],
        ]));

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame(['words' => ['foo', 'bar']], $body['config']);
        $this->assertSame('Prohibited words · foo, bar', $body['summary']);

        $stored = $this->fetchTable('TenantValidationRules')->get($body['id']);
        $this->assertSame(['words' => ['foo', 'bar']], $stored->config);
    }

    public function testAddAcceptsAWellFormedMaxSizeRule(): void
    {
        $this->post('/tenants/2/rules', json_encode(['type' => 'max_size', 'config' => ['max_bytes' => 2048]]));

        $this->assertResponseCode(201);
        $this->assertSame(['max_bytes' => 2048], json_decode((string)$this->_response->getBody(), true)['config']);
    }

    public function testAddRejectsUnknownType(): void
    {
        $this->post('/tenants/2/rules', json_encode(['type' => 'nope', 'config' => ['x' => 1]]));

        $this->assertResponseCode(422);
        $this->assertArrayHasKey('config', json_decode((string)$this->_response->getBody(), true)['errors']);
    }

    public function testAddRejectsStringWhereListExpected(): void
    {
        $this->post('/tenants/2/rules', json_encode(['type' => 'required_metadata', 'config' => ['fields' => 'title']]));
        $this->assertResponseCode(422);
    }

    public function testAddRejectsBlankWordList(): void
    {
        $this->post('/tenants/2/rules', json_encode(['type' => 'prohibited_words', 'config' => ['words' => ['  ']]]));
        $this->assertResponseCode(422);
    }

    public function testAddRejectsNonIntegerByteLimit(): void
    {
        $this->post('/tenants/2/rules', json_encode(['type' => 'max_size', 'config' => ['max_bytes' => '10abc']]));
        $this->assertResponseCode(422);
    }

    public function testAddUnknownTenantReturns404Json(): void
    {
        $this->post('/tenants/999/rules', json_encode(['type' => 'max_size', 'config' => ['max_bytes' => 1]]));

        $this->assertResponseCode(404);
        $this->assertContentType('application/json');
        $this->assertArrayHasKey('message', json_decode((string)$this->_response->getBody(), true));
    }

    public function testDeleteRule(): void
    {
        $this->delete('/tenants/1/rules/2');

        $this->assertResponseCode(204);
        $this->assertSame(0, $this->fetchTable('TenantValidationRules')->find()->where(['id' => 2])->count());
    }

    public function testDeleteRuleWrongTenantReturns404(): void
    {
        $this->delete('/tenants/2/rules/1');
        $this->assertResponseCode(404);
    }
}
