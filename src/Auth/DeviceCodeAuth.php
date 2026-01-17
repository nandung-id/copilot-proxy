<?php

namespace Nandung\CopilotProxy\Auth;

use Nandung\CopilotProxy\Config\ApiConfig;
use Nandung\CopilotProxy\DTO\DeviceCode;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Nandung\CopilotProxy\Http\HttpClient;

/**
 * GitHub Device Code OAuth Flow
 */
class DeviceCodeAuth
{
    protected HttpClient $http;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? new HttpClient();
    }

    /**
     * Get a device code for user authentication
     * 
     * @throws CopilotException
     */
    public function getDeviceCode(): DeviceCode
    {
        $url = ApiConfig::GITHUB_BASE_URL . '/login/device/code';
        
        $response = $this->http->post($url, [
            'client_id' => ApiConfig::GITHUB_CLIENT_ID,
            'scope' => ApiConfig::GITHUB_APP_SCOPES,
        ], ApiConfig::standardHeaders());

        $data = $this->http->parseJson($response);
        
        return DeviceCode::fromArray($data);
    }

    /**
     * Poll for access token after user authorizes
     * 
     * @param DeviceCode $deviceCode The device code from getDeviceCode()
     * @param callable|null $onPoll Optional callback called each poll cycle
     * @return string The GitHub access token
     * @throws CopilotException
     */
    public function pollAccessToken(DeviceCode $deviceCode, ?callable $onPoll = null): string
    {
        $url = ApiConfig::GITHUB_BASE_URL . '/login/oauth/access_token';
        $sleepDuration = ($deviceCode->interval + 1);
        $maxAttempts = (int) ceil($deviceCode->expiresIn / $sleepDuration);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($onPoll !== null) {
                $onPoll($attempt, $maxAttempts);
            }

            try {
                $response = $this->http->post($url, [
                    'client_id' => ApiConfig::GITHUB_CLIENT_ID,
                    'device_code' => $deviceCode->deviceCode,
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                ], ApiConfig::standardHeaders());

                $data = $this->http->parseJson($response);

                if (isset($data['access_token']) && !empty($data['access_token'])) {
                    return $data['access_token'];
                }

                // Check for authorization pending or slow down
                if (isset($data['error'])) {
                    $error = $data['error'];
                    if ($error === 'authorization_pending') {
                        // User hasn't authorized yet, continue polling
                    } elseif ($error === 'slow_down') {
                        $sleepDuration += 5;
                    } elseif ($error === 'expired_token') {
                        throw new CopilotException('Device code expired. Please start the auth process again.');
                    } elseif ($error === 'access_denied') {
                        throw new CopilotException('Access denied by user.');
                    } else {
                        throw new CopilotException("OAuth error: {$error}");
                    }
                }
            } catch (CopilotException $e) {
                // Re-throw if it's an auth-related error
                if (str_contains($e->getMessage(), 'expired') || 
                    str_contains($e->getMessage(), 'denied') ||
                    str_contains($e->getMessage(), 'OAuth error')) {
                    throw $e;
                }
                // Continue polling for transient errors
            }

            sleep($sleepDuration);
        }

        throw new CopilotException('Timed out waiting for user authorization.');
    }
}
