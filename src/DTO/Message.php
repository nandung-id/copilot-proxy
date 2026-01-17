<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Message in a chat completion request/response
 */
class Message
{
    public function __construct(
        public readonly string $role,
        public readonly string|array|null $content,
        public readonly ?string $name = null,
        public readonly ?array $toolCalls = null,
        public readonly ?string $toolCallId = null,
    ) {}

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            role: $data['role'],
            content: $data['content'] ?? null,
            name: $data['name'] ?? null,
            toolCalls: $data['tool_calls'] ?? null,
            toolCallId: $data['tool_call_id'] ?? null,
        );
    }

    /**
     * Convert to array for API request
     */
    public function toArray(): array
    {
        $result = [
            'role' => $this->role,
            'content' => $this->content,
        ];

        if ($this->name !== null) {
            $result['name'] = $this->name;
        }

        if ($this->toolCalls !== null) {
            $result['tool_calls'] = $this->toolCalls;
        }

        if ($this->toolCallId !== null) {
            $result['tool_call_id'] = $this->toolCallId;
        }

        return $result;
    }

    /**
     * Create a user message
     */
    public static function user(string $content): self
    {
        return new self(role: 'user', content: $content);
    }

    /**
     * Create an assistant message
     */
    public static function assistant(string $content): self
    {
        return new self(role: 'assistant', content: $content);
    }

    /**
     * Create a system message
     */
    public static function system(string $content): self
    {
        return new self(role: 'system', content: $content);
    }
}
