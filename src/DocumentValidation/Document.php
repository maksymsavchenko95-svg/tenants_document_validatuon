<?php
declare(strict_types=1);

namespace App\DocumentValidation;

/**
 * Immutable representation of a document submitted for validation.
 */
final readonly class Document
{
    /**
     * @param string $id Identifier of the document (throwaway; nothing is persisted).
     * @param int $tenantId Identifier of the tenant that owns the document.
     * @param string $content Raw textual content of the document.
     * @param array<string, mixed> $metadata Associative metadata map (field => value).
     */
    public function __construct(
        public string $id,
        public int $tenantId,
        public string $content,
        public array $metadata = [],
    ) {
    }
}
