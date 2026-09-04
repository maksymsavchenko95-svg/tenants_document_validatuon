<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TenantsFixture extends TestFixture
{
    public array $records = [
        ['id' => 1, 'name' => 'Acme', 'created' => '2026-01-01 00:00:00', 'modified' => '2026-01-01 00:00:00'],
        ['id' => 2, 'name' => 'Empty Corp', 'created' => '2026-01-01 00:00:00', 'modified' => '2026-01-01 00:00:00'],
    ];
}
