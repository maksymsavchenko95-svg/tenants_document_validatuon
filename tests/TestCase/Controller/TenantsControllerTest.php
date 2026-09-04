<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

final class TenantsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use JsonApiTestTrait;

    protected array $fixtures = ['app.Tenants', 'app.TenantValidationRules'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->useJsonApi();
    }

    public function testIndexRendersHtmlWithEmbeddedData(): void
    {
        $this->configRequest(['headers' => ['Accept' => 'text/html']]);
        $this->get('/tenants');

        $this->assertResponseOk();
        $this->assertResponseContains('id="initial"');
        $this->assertResponseContains('Acme');
        $this->assertResponseContains('"ruleCount":3');
    }

    public function testAddCreatesATenant(): void
    {
        $this->post('/tenants', json_encode(['name' => 'Umbrella']));

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertSame('Umbrella', $body['name']);
        $this->assertSame(1, $this->fetchTable('Tenants')->find()->where(['name' => 'Umbrella'])->count());
    }

    public function testAddRejectsBlankNameWithFieldErrors(): void
    {
        $this->post('/tenants', json_encode(['name' => '   ']));

        $this->assertResponseCode(422);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('name', $body['errors']);
    }

    public function testAddRejectsDuplicateName(): void
    {
        $this->post('/tenants', json_encode(['name' => 'Acme']));

        $this->assertResponseCode(422);
        $this->assertArrayHasKey('name', json_decode((string)$this->_response->getBody(), true)['errors']);
    }

    public function testDeleteRemovesTenantAndCascadesRules(): void
    {
        $this->delete('/tenants/1');

        $this->assertResponseCode(204);
        $this->assertSame(0, $this->fetchTable('Tenants')->find()->where(['id' => 1])->count());
        $this->assertSame(0, $this->fetchTable('TenantValidationRules')->find()->where(['tenant_id' => 1])->count());
    }

    public function testDeleteUnknownReturns404(): void
    {
        $this->delete('/tenants/999');
        $this->assertResponseCode(404);
    }
}
