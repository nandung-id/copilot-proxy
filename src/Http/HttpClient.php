<?php

namespace Nandung\CopilotProxy\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Nandung\CopilotProxy\Exceptions\CopilotException;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Client wrapper for API requests
 */
class HttpClient
{
    protected Client $client;
    protected array $defaultHeaders = [];

    public function __construct(array $config = [])
    {
        $this->client = new Client(array_merge([
            'timeout' => 120,
            'connect_timeout' => 30,
        ], $config));
    }

    /**
     * Set default headers for all requests
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /**
     * Make a GET request
     */
    public function get(string $url, array $headers = []): ResponseInterface
    {
        return $this->request('GET', $url, $headers);
    }

    /**
     * Make a POST request with JSON body
     */
    public function post(string $url, array $data, array $headers = []): ResponseInterface
    {
        return $this->request('POST', $url, $headers, json_encode($data));
    }

    /**
     * Make a POST request and return streaming response
     */
    public function postStream(string $url, array $data, array $headers = []): ResponseInterface
    {
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        
        try {
            return $this->client->post($url, [
                'headers' => $allHeaders,
                'json' => $data,
                'stream' => true,
            ]);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                throw CopilotException::fromResponse($e->getMessage(), $e->getResponse());
            }
            throw new CopilotException($e->getMessage(), $e->getCode(), null, null, $e);
        } catch (GuzzleException $e) {
            throw new CopilotException($e->getMessage(), $e->getCode(), null, null);
        }
    }

    /**
     * Make an HTTP request
     */
    protected function request(string $method, string $url, array $headers = [], ?string $body = null): ResponseInterface
    {
        $allHeaders = array_merge($this->defaultHeaders, $headers);
        
        try {
            $options = ['headers' => $allHeaders];
            if ($body !== null) {
                $options['body'] = $body;
            }
            
            return $this->client->request($method, $url, $options);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                throw CopilotException::fromResponse($e->getMessage(), $e->getResponse());
            }
            throw new CopilotException($e->getMessage(), $e->getCode(), null, null, $e);
        } catch (GuzzleException $e) {
            throw new CopilotException($e->getMessage(), $e->getCode(), null, null);
        }
    }

    /**
     * Parse JSON response
     */
    public function parseJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new CopilotException('Failed to parse JSON response: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Read SSE stream and yield events
     * 
     * @return \Generator<array{event?: string, data: string}>
     */
    public function readSSEStream(ResponseInterface $response): \Generator
    {
        $body = $response->getBody();
        $buffer = '';
        
        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;
            
            // Process complete lines
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                // Parse SSE format: "data: {json}"
                if (str_starts_with($line, 'data: ')) {
                    $data = substr($line, 6);
                    yield ['data' => $data];
                } elseif (str_starts_with($line, 'event: ')) {
                    $event = substr($line, 7);
                    yield ['event' => $event];
                }
            }
        }
    }
}
