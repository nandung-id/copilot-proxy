<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Copilot token response
 */
class CopilotToken
{
    public function __construct(
        public readonly string $token,
        public readonly int $expiresAt,
        public readonly int $refreshIn,
    ) {}

    /**
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            expiresAt: $data['expires_at'],
            refreshIn: $data['refresh_in'],
        );
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return time() >= $this->expiresAt;
    }

    /**
     * Check if token is expiring soon (within 5 minutes)
     */
    public function isExpiringSoon(): bool
    {
        return time() >= ($this->expiresAt - 300);
    }

    /**
     * Get seconds until expiry
     */
    public function getSecondsUntilExpiry(): int
    {
        return max(0, $this->expiresAt - time());
    }
}
