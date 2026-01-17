<?php

namespace Nandung\CopilotProxy\DTO;

/**
 * Device code response from GitHub OAuth
 */
class DeviceCode
{
    public function __construct(
        public readonly string $deviceCode,
        public readonly string $userCode,
        public readonly string $verificationUri,
        public readonly int $expiresIn,
        public readonly int $interval,
    ) {}

    /**
     * Create from API response
     */
    public static function fromArray(array $data): self
    {
        return new self(
            deviceCode: $data['device_code'],
            userCode: $data['user_code'],
            verificationUri: $data['verification_uri'],
            expiresIn: $data['expires_in'],
            interval: $data['interval'],
        );
    }
}
