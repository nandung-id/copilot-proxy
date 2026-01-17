<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Streaming chat completion chunk
 */
class ChatCompletionChunk
{
    public function __construct(
        public readonly string $id,
        public readonly string $object,
        public readonly int $created,
        public readonly string $model,
        public readonly array $choices,
        public readonly ?array $usage = null,
        public readonly ?string $systemFingerprint = null,
    ) {}

    /**
     * Create from API response data
     */
    public static function fromArray(array $data): self
    {
        $choices = array_map(
            fn($choice) => ChunkChoice::fromArray($choice),
            $data['choices'] ?? []
        );

        return new self(
            id: $data['id'],
            object: $data['object'] ?? 'chat.completion.chunk',
            created: $data['created'] ?? time(),
            model: $data['model'] ?? 'unknown',
            choices: $choices,
            usage: $data['usage'] ?? null,
            systemFingerprint: $data['system_fingerprint'] ?? null,
        );
    }

    /**
     * Get delta content from first choice
     */
    public function getDeltaContent(): ?string
    {
        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->delta['content'] ?? null;
    }

    /**
     * Get delta role from first choice
     */
    public function getDeltaRole(): ?string
    {
        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->delta['role'] ?? null;
    }

    /**
     * Check if this is the final chunk
     */
    public function isFinished(): bool
    {
        if (empty($this->choices)) {
            return false;
        }

        return $this->choices[0]->finishReason !== null;
    }
}

/**
 * A choice in the streaming chunk
 */
class ChunkChoice
{
    public function __construct(
        public readonly int $index,
        public readonly array $delta,
        public readonly ?string $finishReason = null,
        public readonly mixed $logprobs = null,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            index: $data['index'],
            delta: $data['delta'] ?? [],
            finishReason: $data['finish_reason'] ?? null,
            logprobs: $data['logprobs'] ?? null,
        );
    }
}
