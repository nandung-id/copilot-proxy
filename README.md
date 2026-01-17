# Copilot Proxy PHP Library

PHP library untuk mengakses GitHub Copilot API dengan interface OpenAI-compatible.

## Requirements

- PHP 8.1+
- Active GitHub Copilot subscription

## Installation

```bash
composer require nandung/copilot-proxy
```

## Quick Start

### 1. Authentication (First Time Only)

```php
<?php

use Nandung\CopilotProxy\CopilotProxy;

// Get device code
$auth = CopilotProxy::auth();
$deviceCode = $auth->getDeviceCode();

echo "Go to: {$deviceCode->verificationUri}\n";
echo "Enter code: {$deviceCode->userCode}\n";

// Wait for user to authorize (polls automatically)
$githubToken = $auth->pollAccessToken($deviceCode, function($attempt, $max) {
    echo "Waiting for authorization... ({$attempt}/{$max})\n";
});

// Save token for later use
file_put_contents('github_token.txt', $githubToken);
echo "Token saved!\n";
```

### 2. Using the API

```php
<?php

use Nandung\CopilotProxy\CopilotProxy;

$proxy = new CopilotProxy(file_get_contents('github_token.txt'));

// Simple question
$answer = $proxy->ask('What is the capital of France?');
echo $answer; // Paris

// Get user info
$user = $proxy->getUser();
echo "Logged in as: {$user['login']}\n";
```

## API Reference

### Chat Completions

```php
// Non-streaming
$response = $proxy->chat([
    'model' => 'gpt-4.1',
    'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant.'],
        ['role' => 'user', 'content' => 'Hello!']
    ],
    'max_tokens' => 1000,
    'temperature' => 0.7,
]);

echo $response->getContent();
echo "Tokens used: {$response->usage['total_tokens']}\n";

// Streaming
foreach ($proxy->chatStream([
    'model' => 'gpt-4.1',
    'messages' => [['role' => 'user', 'content' => 'Tell me a story']]
]) as $chunk) {
    echo $chunk->getDeltaContent();
    flush();
}
```

### Available Models

```php
$models = $proxy->models();

foreach ($models as $model) {
    echo "- {$model->id} ({$model->vendor})\n";
    echo "  Max tokens: {$model->getMaxOutputTokens()}\n";
}
```

### Embeddings

```php
$embedding = $proxy->embeddings('Hello world', 'text-embedding-3-small');
$vector = $embedding->getFirstEmbedding();
echo "Vector dimensions: " . count($vector) . "\n";
```

### Tools / Function Calling

```php
$response = $proxy->chat([
    'model' => 'gpt-4.1',
    'messages' => [
        ['role' => 'user', 'content' => 'What is the weather in Tokyo?']
    ],
    'tools' => [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_weather',
                'description' => 'Get weather for a location',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => ['type' => 'string']
                    ],
                    'required' => ['location']
                ]
            ]
        ]
    ]
]);

$toolCalls = $response->getMessage()->toolCalls ?? [];
foreach ($toolCalls as $call) {
    echo "Function: {$call['function']['name']}\n";
    echo "Arguments: {$call['function']['arguments']}\n";
}
```

## Account Types

```php
// Individual (default)
$proxy = new CopilotProxy($token, 'individual');

// Business
$proxy = new CopilotProxy($token, 'business');

// Enterprise
$proxy = new CopilotProxy($token, 'enterprise');
```

## Error Handling

```php
use Nandung\CopilotProxy\Exceptions\CopilotException;

try {
    $response = $proxy->chat([...]);
} catch (CopilotException $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "Status: {$e->getStatusCode()}\n";
    
    if ($errorData = $e->getErrorData()) {
        print_r($errorData);
    }
}
```

## Laravel Integration

```php
// In AppServiceProvider

use Nandung\CopilotProxy\CopilotProxy;

public function register()
{
    $this->app->singleton(CopilotProxy::class, function () {
        return new CopilotProxy(config('services.copilot.token'));
    });
}

// Usage in controller
public function index(CopilotProxy $proxy)
{
    return $proxy->ask('Hello!');
}
```

## License

MIT
