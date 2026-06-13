<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class GatewayResponse
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public bool $delivered = false,
        public bool $retryable = false,
        public ?string $errorMessage = null,
        public int $httpStatus = 200,
    ) {
    }

    public static function success(string $providerMessageId, bool $delivered = true): self
    {
        return new self(
            success: true,
            providerMessageId: $providerMessageId,
            delivered: $delivered,
            retryable: false,
            httpStatus: 200,
        );
    }

    public static function permanentFailure(string $errorMessage, int $httpStatus = 400): self
    {
        return new self(
            success: false,
            retryable: false,
            errorMessage: $errorMessage,
            httpStatus: $httpStatus,
        );
    }

    public static function temporaryFailure(string $errorMessage, int $httpStatus = 503): self
    {
        return new self(
            success: false,
            retryable: true,
            errorMessage: $errorMessage,
            httpStatus: $httpStatus,
        );
    }
}
