<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

/**
 * Configures integration requests the way the Vue frontend makes them:
 * a JSON body, JSON accepted.
 */
trait JsonApiTestTrait
{
    protected function useJsonApi(): void
    {
        $this->configRequest(['headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]]);
    }
}
