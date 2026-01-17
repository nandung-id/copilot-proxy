<?php

namespace Nandung\CopilotProxy\Services;

use Nandung\CopilotProxy\Auth\TokenManager;
use Nandung\CopilotProxy\DTO\EmbeddingResponse;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;

/**
 * Embeddings service
 */
class EmbeddingService
{
    protected HttpClient $http;
    protected TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager, ?HttpClient $http = null)
    {
        $this->tokenManager = $tokenManager;
        $this->http = $http ?? new HttpClient();
    }

    /**
     * Create embeddings for text
     * 
     * @param string|array $input Text or array of texts to embed
     * @param string $model Model to use for embeddings
     * @return EmbeddingResponse
     * @throws CopilotException
     */
    public function createEmbedding(string|array $input, string $model): EmbeddingResponse
    {
        $url = $this->tokenManager->getCopilotBaseUrl() . '/embeddings';
        $headers = $this->tokenManager->getCopilotHeaders();
        
        $payload = [
            'input' => $input,
            'model' => $model,
        ];
        
        $response = $this->http->post($url, $payload, $headers);
        
        if ($response->getStatusCode() !== 200) {
            throw CopilotException::fromResponse('Failed to create embeddings', $response);
        }
        
        $data = $this->http->parseJson($response);
        
        return EmbeddingResponse::fromArray($data);
    }

    /**
     * Create embedding for a single text
     * 
     * @param string $text Text to embed
     * @param string $model Model to use
     * @return array The embedding vector
     * @throws CopilotException
     */
    public function embed(string $text, string $model): array
    {
        $response = $this->createEmbedding($text, $model);
        return $response->getFirstEmbedding() ?? [];
    }
}
