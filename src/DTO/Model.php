<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Model information
 */
class Model
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $vendor,
        public readonly string $version,
        public readonly bool $preview,
        public readonly bool $modelPickerEnabled,
        public readonly array $capabilities,
        public readonly ?array $policy = null,
    ) {}

    /**
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            vendor: $data['vendor'],
            version: $data['version'],
            preview: $data['preview'] ?? false,
            modelPickerEnabled: $data['model_picker_enabled'] ?? true,
            capabilities: $data['capabilities'] ?? [],
            policy: $data['policy'] ?? null,
        );
    }

    /**
     * Get max context window tokens
     */
    public function getMaxContextTokens(): ?int
    {
        return $this->capabilities['limits']['max_context_window_tokens'] ?? null;
    }

    /**
     * Get max output tokens
     */
    public function getMaxOutputTokens(): ?int
    {
        return $this->capabilities['limits']['max_output_tokens'] ?? null;
    }

    /**
     * Check if model supports tool calls
     */
    public function supportsToolCalls(): bool
    {
        return $this->capabilities['supports']['tool_calls'] ?? false;
    }

    /**
     * Convert to OpenAI-compatible format
     */
    public function toOpenAIFormat(): array
    {
        return [
            'id' => $this->id,
            'object' => 'model',
            'type' => 'model',
            'created' => 0,
            'created_at' => (new \DateTime())->format('c'),
            'owned_by' => $this->vendor,
            'display_name' => $this->name,
        ];
    }
}
