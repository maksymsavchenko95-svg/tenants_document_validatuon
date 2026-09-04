<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TenantValidationRulesFixture extends TestFixture
{
    public array $records = [
        // Acme (id 1): three rules, in id order.
        ['id' => 1, 'tenant_id' => 1, 'type' => 'max_size', 'config' => '{"max_bytes":10240}', 'created' => '2026-01-01 00:00:00'],
        ['id' => 2, 'tenant_id' => 1, 'type' => 'required_metadata', 'config' => '{"fields":["author","title"]}', 'created' => '2026-01-01 00:00:00'],
        ['id' => 3, 'tenant_id' => 1, 'type' => 'prohibited_words', 'config' => '{"words":["foo","bar"]}', 'created' => '2026-01-01 00:00:00'],
        // Empty Corp (id 2): no rules.
    ];
}
