<?php

namespace Nandung\CopilotProxy\Services;

use Nandung\CopilotProxy\Auth\TokenManager;
use Nandung\CopilotProxy\DTO\ChatCompletionChunk;
use Nandung\CopilotProxy\DTO\ChatCompletionResponse;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;

/**
 * Chat completion service
 */
class ChatService
{
    protected HttpClient $http;
    protected TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager, ?HttpClient $http = null)
    {
        $this->tokenManager = $tokenManager;
        $this->http = $http ?? new HttpClient();
    }

    /**
     * Create a chat completion (non-streaming)
     * 
     * @param array $payload Chat completion request payload
     * @return ChatCompletionResponse
     * @throws CopilotException
     */
    public function createCompletion(array $payload): ChatCompletionResponse
    {
        $payload['stream'] = false;
        
        $response = $this->makeRequest($payload);
        $data = $this->http->parseJson($response);
        
        return ChatCompletionResponse::fromArray($data);
    }

    /**
     * Create a streaming chat completion
     * 
     * @param array $payload Chat completion request payload
     * @return \Generator<ChatCompletionChunk>
     * @throws CopilotException
     */
    public function createStreamingCompletion(array $payload): \Generator
    {
        $payload['stream'] = true;
        
        $response = $this->makeStreamingRequest($payload);
        
        foreach ($this->http->readSSEStream($response) as $event) {
            if (!isset($event['data'])) {
                continue;
            }
            
            $data = $event['data'];
            
            if ($data === '[DONE]') {
                break;
            }
            
            try {
                $parsed = json_decode($data, true);
                if ($parsed !== null) {
                    yield ChatCompletionChunk::fromArray($parsed);
                }
            } catch (\Throwable) {
                // Skip malformed chunks
            }
        }
    }

    /**
     * Make API request
     */
    protected function makeRequest(array $payload): \Psr\Http\Message\ResponseInterface
    {
        $url = $this->tokenManager->getCopilotBaseUrl() . '/chat/completions';
        $headers = $this->buildHeaders($payload);
        
        return $this->http->post($url, $payload, $headers);
    }

    /**
     * Make streaming API request
     */
    protected function makeStreamingRequest(array $payload): \Psr\Http\Message\ResponseInterface
    {
        $url = $this->tokenManager->getCopilotBaseUrl() . '/chat/completions';
        $headers = $this->buildHeaders($payload);
        
        return $this->http->postStream($url, $payload, $headers);
    }

    /**
     * Build request headers
     */
    protected function buildHeaders(array $payload): array
    {
        // Check for vision content
        $hasVision = $this->hasVisionContent($payload);
        
        $headers = $this->tokenManager->getCopilotHeaders($hasVision);
        
        // Add X-Initiator header based on message roles
        $isAgentCall = $this->isAgentCall($payload);
        $headers['X-Initiator'] = $isAgentCall ? 'agent' : 'user';
        
        return $headers;
    }

    /**
     * Check if payload contains vision content
     */
    protected function hasVisionContent(array $payload): bool
    {
        foreach ($payload['messages'] ?? [] as $message) {
            if (is_array($message['content'] ?? null)) {
                foreach ($message['content'] as $part) {
                    if (($part['type'] ?? '') === 'image_url') {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if this is an agent call (has assistant or tool messages)
     */
    protected function isAgentCall(array $payload): bool
    {
        foreach ($payload['messages'] ?? [] as $message) {
            if (in_array($message['role'] ?? '', ['assistant', 'tool'])) {
                return true;
            }
        }
        return false;
    }
}
