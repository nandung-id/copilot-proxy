<?php

namespace Nandung\CopilotProxy;

use Nandung\CopilotProxy\Auth\DeviceCodeAuth;
use Nandung\CopilotProxy\Auth\TokenManager;
use Nandung\CopilotProxy\DTO\ChatCompletionChunk;
use Nandung\CopilotProxy\DTO\ChatCompletionResponse;
use Nandung\CopilotProxy\DTO\EmbeddingResponse;
use Nandung\CopilotProxy\DTO\Model;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;
use Nandung\CopilotProxy\Services\ChatService;
use Nandung\CopilotProxy\Services\EmbeddingService;
use Nandung\CopilotProxy\Services\ModelService;

/**
 * Main Copilot Proxy client
 * 
 * A PHP library for accessing GitHub Copilot API with OpenAI-compatible interface.
 * 
 * @example
 * ```php
 * $proxy = new CopilotProxy($githubToken);
 * 
 * // Chat completion
 * $response = $proxy->chat([
 *     'model' => 'gpt-4.1',
 *     'messages' => [
 *         ['role' => 'user', 'content' => 'Hello!']
 *     ]
 * ]);
 * echo $response->getContent();
 * 
 * // Streaming
 * foreach ($proxy->chatStream([...]) as $chunk) {
 *     echo $chunk->getDeltaContent();
 * }
 * ```
 */
class CopilotProxy
{
    protected TokenManager $tokenManager;
    protected HttpClient $http;
    protected ?ChatService $chatService = null;
    protected ?ModelService $modelService = null;
    protected ?EmbeddingService $embeddingService = null;

    /**
     * Create a new CopilotProxy instance
     * 
     * @param string $githubToken GitHub access token (obtained via DeviceCodeAuth)
     * @param string $accountType Account type: 'individual', 'business', or 'enterprise'
     * @param string|null $vsCodeVersion VS Code version to emulate
     */
    public function __construct(
        string $githubToken,
        string $accountType = 'individual',
        ?string $vsCodeVersion = null
    ) {
        $this->http = new HttpClient();
        $this->tokenManager = new TokenManager(
            $githubToken,
            $accountType,
            $vsCodeVersion,
            $this->http
        );
    }

    /**
     * Create CopilotProxy from token file
     * 
     * @param string $tokenPath Path to file containing GitHub token
     * @param string $accountType Account type
     * @return self
     * @throws CopilotException
     */
    public static function fromTokenFile(string $tokenPath, string $accountType = 'individual'): self
    {
        if (!file_exists($tokenPath)) {
            throw new CopilotException("Token file not found: {$tokenPath}");
        }
        
        $token = trim(file_get_contents($tokenPath));
        
        if (empty($token)) {
            throw new CopilotException("Token file is empty: {$tokenPath}");
        }
        
        return new self($token, $accountType);
    }

    /**
     * Get the authentication helper for device code flow
     */
    public static function auth(): DeviceCodeAuth
    {
        return new DeviceCodeAuth();
    }

    /**
     * Get current user info
     * 
     * @return array User information from GitHub API
     * @throws CopilotException
     */
    public function getUser(): array
    {
        return $this->tokenManager->getUser();
    }

    /**
     * Get the chat service
     */
    public function chatService(): ChatService
    {
        if ($this->chatService === null) {
            $this->chatService = new ChatService($this->tokenManager, $this->http);
        }
        return $this->chatService;
    }

    /**
     * Get the model service
     */
    public function modelService(): ModelService
    {
        if ($this->modelService === null) {
            $this->modelService = new ModelService($this->tokenManager, $this->http);
        }
        return $this->modelService;
    }

    /**
     * Get the embedding service
     */
    public function embeddingService(): EmbeddingService
    {
        if ($this->embeddingService === null) {
            $this->embeddingService = new EmbeddingService($this->tokenManager, $this->http);
        }
        return $this->embeddingService;
    }

    // ========================================
    // Convenience methods
    // ========================================

    /**
     * Create a chat completion
     * 
     * @param array $payload Chat completion request
     * @return ChatCompletionResponse
     * @throws CopilotException
     */
    public function chat(array $payload): ChatCompletionResponse
    {
        return $this->chatService()->createCompletion($payload);
    }

    /**
     * Create a streaming chat completion
     * 
     * @param array $payload Chat completion request
     * @return \Generator<ChatCompletionChunk>
     * @throws CopilotException
     */
    public function chatStream(array $payload): \Generator
    {
        return $this->chatService()->createStreamingCompletion($payload);
    }

    /**
     * Get available models
     * 
     * @return Model[]
     * @throws CopilotException
     */
    public function models(): array
    {
        return $this->modelService()->getModels();
    }

    /**
     * Create embeddings
     * 
     * @param string|array $input Text(s) to embed
     * @param string $model Model to use
     * @return EmbeddingResponse
     * @throws CopilotException
     */
    public function embeddings(string|array $input, string $model): EmbeddingResponse
    {
        return $this->embeddingService()->createEmbedding($input, $model);
    }

    /**
     * Simple chat - send a message and get a response
     * 
     * @param string $message User message
     * @param string $model Model to use (default: gpt-4.1)
     * @param string|null $systemPrompt Optional system prompt
     * @return string Response content
     * @throws CopilotException
     */
    public function ask(string $message, string $model = 'gpt-4.1', ?string $systemPrompt = null): string
    {
        $messages = [];
        
        if ($systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $response = $this->chat([
            'model' => $model,
            'messages' => $messages,
        ]);
        
        return $response->getContent() ?? '';
    }

    /**
     * Get the token manager
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }

    /**
     * Refresh the Copilot token
     * 
     * @throws CopilotException
     */
    public function refreshToken(): void
    {
        $this->tokenManager->refreshCopilotToken();
    }
}
