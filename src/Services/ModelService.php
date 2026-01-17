<?php

namespace Nandung\CopilotProxy\Services;

use Nandung\CopilotProxy\Auth\TokenManager;
use Nandung\CopilotProxy\DTO\Model;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;

/**
 * Model listing service
 */
class ModelService
{
    protected HttpClient $http;
    protected TokenManager $tokenManager;
    protected ?array $cachedModels = null;

    public function __construct(TokenManager $tokenManager, ?HttpClient $http = null)
    {
        $this->tokenManager = $tokenManager;
        $this->http = $http ?? new HttpClient();
    }

    /**
     * Get available models
     * 
     * @param bool $useCache Whether to use cached models if available
     * @return Model[]
     * @throws CopilotException
     */
    public function getModels(bool $useCache = true): array
    {
        if ($useCache && $this->cachedModels !== null) {
            return $this->cachedModels;
        }

        $url = $this->tokenManager->getCopilotBaseUrl() . '/models';
        $headers = $this->tokenManager->getCopilotHeaders();
        
        $response = $this->http->get($url, $headers);
        
        if ($response->getStatusCode() !== 200) {
            throw CopilotException::fromResponse('Failed to get models', $response);
        }
        
        $data = $this->http->parseJson($response);
        
        $models = array_map(
            fn($item) => Model::fromArray($item),
            $data['data'] ?? []
        );
        
        $this->cachedModels = $models;
        
        return $models;
    }

    /**
     * Get a specific model by ID
     * 
     * @throws CopilotException
     */
    public function getModel(string $modelId): ?Model
    {
        $models = $this->getModels();
        
        foreach ($models as $model) {
            if ($model->id === $modelId) {
                return $model;
            }
        }
        
        return null;
    }

    /**
     * Get models in OpenAI-compatible format
     * 
     * @throws CopilotException
     */
    public function getModelsOpenAIFormat(): array
    {
        $models = $this->getModels();
        
        return [
            'object' => 'list',
            'data' => array_map(fn($model) => $model->toOpenAIFormat(), $models),
            'has_more' => false,
        ];
    }

    /**
     * Clear cached models
     */
    public function clearCache(): void
    {
        $this->cachedModels = null;
    }
}
