<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Chat completion response
 */
class ChatCompletionResponse
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
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        $choices = array_map(
            fn($choice) => ChatCompletionChoice::fromArray($choice),
            $data['choices'] ?? []
        );

        return new self(
            id: $data['id'],
            object: $data['object'],
            created: $data['created'],
            model: $data['model'],
            choices: $choices,
            usage: $data['usage'] ?? null,
            systemFingerprint: $data['system_fingerprint'] ?? null,
        );
    }

    /**
     * Get the first choice's message content
     */
    public function getContent(): ?string
    {
        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->message->content;
    }

    /**
     * Get the first choice's message
     */
    public function getMessage(): ?Message
    {
        if (empty($this->choices)) {
            return null;
        }

        return $this->choices[0]->message;
    }
}

/**
 * A choice in the chat completion response
 */
class ChatCompletionChoice
{
    public function __construct(
        public readonly int $index,
        public readonly Message $message,
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
            message: Message::fromArray($data['message']),
            finishReason: $data['finish_reason'] ?? null,
            logprobs: $data['logprobs'] ?? null,
        );
    }
}
