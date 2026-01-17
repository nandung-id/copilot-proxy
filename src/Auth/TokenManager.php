<?php

namespace Nandung\CopilotProxy\Auth;

use Nandung\CopilotProxy\Config\ApiConfig;
use Nandung\CopilotProxy\DTO\CopilotToken;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;

/**
 * Token manager for GitHub and Copilot tokens
 */
class TokenManager
{
    protected HttpClient $http;
    protected string $githubToken;
    protected ?CopilotToken $copilotToken = null;
    protected string $accountType;
    protected ?string $vsCodeVersion;

    public function __construct(
        string $githubToken,
        string $accountType = 'individual',
        ?string $vsCodeVersion = null,
        ?HttpClient $http = null
    ) {
        $this->http = $http ?? new HttpClient();
        $this->githubToken = $githubToken;
        $this->accountType = $accountType;
        $this->vsCodeVersion = $vsCodeVersion ?? ApiConfig::VSCODE_VERSION;
    }

    /**
     * Get the GitHub token
     */
    public function getGitHubToken(): string
    {
        return $this->githubToken;
    }

    /**
     * Get Copilot token, refreshing if necessary
     * 
     * @throws CopilotException
     */
    public function getCopilotToken(): CopilotToken
    {
        if ($this->copilotToken === null || $this->copilotToken->isExpiringSoon()) {
            $this->copilotToken = $this->fetchCopilotToken();
        }

        return $this->copilotToken;
    }

    /**
     * Force refresh the Copilot token
     * 
     * @throws CopilotException
     */
    public function refreshCopilotToken(): CopilotToken
    {
        $this->copilotToken = $this->fetchCopilotToken();
        return $this->copilotToken;
    }

    /**
     * Fetch a new Copilot token from GitHub
     * 
     * @throws CopilotException
     */
    protected function fetchCopilotToken(): CopilotToken
    {
        $url = ApiConfig::GITHUB_API_BASE_URL . '/copilot_internal/v2/token';
        
        $headers = ApiConfig::githubHeaders($this->githubToken, $this->vsCodeVersion);
        
        $response = $this->http->get($url, $headers);
        
        if ($response->getStatusCode() !== 200) {
            throw CopilotException::fromResponse('Failed to get Copilot token', $response);
        }

        $data = $this->http->parseJson($response);
        
        return CopilotToken::fromArray($data);
    }

    /**
     * Get GitHub user info
     * 
     * @throws CopilotException
     */
    public function getUser(): array
    {
        $url = ApiConfig::GITHUB_API_BASE_URL . '/user';
        
        $headers = ApiConfig::githubHeaders($this->githubToken, $this->vsCodeVersion);
        
        $response = $this->http->get($url, $headers);
        
        if ($response->getStatusCode() !== 200) {
            throw CopilotException::fromResponse('Failed to get user info', $response);
        }

        return $this->http->parseJson($response);
    }

    /**
     * Get account type
     */
    public function getAccountType(): string
    {
        return $this->accountType;
    }

    /**
     * Get VSCode version
     */
    public function getVSCodeVersion(): string
    {
        return $this->vsCodeVersion;
    }

    /**
     * Get headers for Copilot API requests
     * 
     * @throws CopilotException
     */
    public function getCopilotHeaders(bool $vision = false): array
    {
        $token = $this->getCopilotToken();
        return ApiConfig::copilotHeaders($token->token, $this->vsCodeVersion, $vision);
    }

    /**
     * Get Copilot base URL
     */
    public function getCopilotBaseUrl(): string
    {
        return ApiConfig::getCopilotBaseUrl($this->accountType);
    }
}
