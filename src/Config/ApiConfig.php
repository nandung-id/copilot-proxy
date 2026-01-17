<?php

namespace Nandung\CopilotProxy\Config;

/**
 * API Configuration constants and header builders
 */
class ApiConfig
{
    public const COPILOT_VERSION = '0.26.7';
    public const EDITOR_PLUGIN_VERSION = 'copilot-chat/0.26.7';
    public const USER_AGENT = 'GitHubCopilotChat/0.26.7';
    public const API_VERSION = '2025-04-01';
    
    public const GITHUB_API_BASE_URL = 'https://api.github.com';
    public const GITHUB_BASE_URL = 'https://github.com';
    public const GITHUB_CLIENT_ID = 'Iv1.b507a08c87ecfe98';
    public const GITHUB_APP_SCOPES = 'read:user';
    
    public const VSCODE_VERSION = '1.96.0';

    /**
     * Get base URL for Copilot API based on account type
     */
    public static function getCopilotBaseUrl(string $accountType = 'individual'): string
    {
        if ($accountType === 'individual') {
            return 'https://api.githubcopilot.com';
        }
        return "https://api.{$accountType}.githubcopilot.com";
    }

    /**
     * Get standard headers for JSON requests
     */
    public static function standardHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get headers for GitHub API requests
     */
    public static function githubHeaders(string $githubToken, ?string $vsCodeVersion = null): array
    {
        $version = $vsCodeVersion ?? self::VSCODE_VERSION;
        
        return array_merge(self::standardHeaders(), [
            'Authorization' => "token {$githubToken}",
            'editor-version' => "vscode/{$version}",
            'editor-plugin-version' => self::EDITOR_PLUGIN_VERSION,
            'User-Agent' => self::USER_AGENT,
            'x-github-api-version' => self::API_VERSION,
            'x-vscode-user-agent-library-version' => 'electron-fetch',
        ]);
    }

    /**
     * Get headers for Copilot API requests
     */
    public static function copilotHeaders(
        string $copilotToken,
        ?string $vsCodeVersion = null,
        bool $vision = false
    ): array {
        $version = $vsCodeVersion ?? self::VSCODE_VERSION;
        
        $headers = [
            'Authorization' => "Bearer {$copilotToken}",
            'Content-Type' => 'application/json',
            'copilot-integration-id' => 'vscode-chat',
            'editor-version' => "vscode/{$version}",
            'editor-plugin-version' => self::EDITOR_PLUGIN_VERSION,
            'User-Agent' => self::USER_AGENT,
            'openai-intent' => 'conversation-panel',
            'x-github-api-version' => self::API_VERSION,
            'x-request-id' => self::generateUuid(),
            'x-vscode-user-agent-library-version' => 'electron-fetch',
        ];

        if ($vision) {
            $headers['copilot-vision-request'] = 'true';
        }

        return $headers;
    }

    /**
     * Generate a UUID v4
     */
    private static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
