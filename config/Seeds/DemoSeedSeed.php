<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * DemoSeed seed.
 */
class DemoSeedSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/5/guides/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $existing = array_column($this->fetchAll('SELECT name FROM tenants'), 'name');

        if (!in_array('WithRules', $existing, true)) {
            $this->table('tenants')->insert([
                ['name' => 'Acme', 'created' => $now, 'modified' => $now],
            ])->saveData();

            $acme = $this->fetchRow("SELECT id FROM tenants WHERE name = 'Acme'");
            $acmeId = (int)$acme['id'];

            $this->table('tenant_validation_rules')->insert([
                ['tenant_id' => $acmeId, 'type' => 'max_size', 'config' => json_encode(['max_bytes' => 10240]), 'created' => $now],
                ['tenant_id' => $acmeId, 'type' => 'required_metadata', 'config' => json_encode(['fields' => ['author', 'title']]), 'created' => $now],
                ['tenant_id' => $acmeId, 'type' => 'prohibited_words', 'config' => json_encode(['words' => ['foo', 'bar']]), 'created' => $now],
            ])->saveData();
        }

        if (!in_array('Empty Corp', $existing, true)) {
            $this->table('tenants')->insert([
                ['name' => 'Empty Corp', 'created' => $now, 'modified' => $now],
            ])->saveData();
        }
    }
}
