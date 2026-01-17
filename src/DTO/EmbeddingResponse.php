<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Embedding response
 */
class EmbeddingResponse
{
    public function __construct(
        public readonly string $object,
        public readonly array $data,
        public readonly string $model,
        public readonly array $usage,
    ) {}

    /**
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        $embeddings = array_map(
            fn($item) => Embedding::fromArray($item),
            $data['data'] ?? []
        );

        return new self(
            object: $data['object'],
            data: $embeddings,
            model: $data['model'],
            usage: $data['usage'] ?? [],
        );
    }

    /**
     * Get the first embedding vector
     */
    public function getFirstEmbedding(): ?array
    {
        if (empty($this->data)) {
            return null;
        }

        return $this->data[0]->embedding;
    }
}

/**
 * Single embedding
 */
class Embedding
{
    public function __construct(
        public readonly string $object,
        public readonly array $embedding,
        public readonly int $index,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            object: $data['object'],
            embedding: $data['embedding'],
            index: $data['index'],
        );
    }
}
