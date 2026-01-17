<?php

namespace Nandung\CopilotProxy\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Custom exception for Copilot API errors
 */
class CopilotException extends Exception
{
    protected ?ResponseInterface $response;
    protected ?array $errorData;

    public function __construct(
        string $message,
        int $code = 0,
        ?ResponseInterface $response = null,
        ?array $errorData = null,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
        $this->errorData = $errorData;
    }

    /**
     * Create exception from HTTP response
     */
    public static function fromResponse(string $message, ResponseInterface $response): self
    {
        $body = (string) $response->getBody();
        $errorData = null;
        
        try {
            $errorData = json_decode($body, true);
        } catch (\Throwable) {
            // Body is not JSON
        }

        return new self(
            $message,
            $response->getStatusCode(),
            $response,
            $errorData
        );
    }

    /**
     * Get the HTTP response if available
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get parsed error data if available
     */
    public function getErrorData(): ?array
    {
        return $this->errorData;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->response?->getStatusCode() ?? $this->code;
    }
}
