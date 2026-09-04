<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

class AppController extends Controller
{
    /**
     * @param array<string, mixed>|list<mixed> $body
     */
    protected function json(array $body, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Reduce a CakePHP entity-errors structure to `{field: [messages]}`.
     *
     * @param array<string, mixed> $errors
     * @return array<string, list<string>>
     */
    protected function fieldErrors(array $errors): array
    {
        $out = [];
        foreach ($errors as $field => $messages) {
            $flat = [];
            $messages = is_array($messages) ? $messages : [$messages];
            array_walk_recursive($messages, static function ($message) use (&$flat): void {
                if (is_string($message)) {
                    $flat[] = $message;
                }
            });
            $out[(string)$field] = $flat;
        }

        return $out;
    }
}
