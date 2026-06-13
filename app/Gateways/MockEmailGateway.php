<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\EmailGatewayInterface;
use App\DTO\GatewayResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockEmailGateway implements EmailGatewayInterface
{
    public function send(string $recipient, string $message): GatewayResponse
    {
        Log::info('Mock Email gateway send', [
            'recipient' => $recipient,
            'message_length' => strlen($message),
        ]);

        if (str_ends_with($recipient, '@invalid.test')) {
            return GatewayResponse::permanentFailure('Invalid email address', 400);
        }

        if (str_contains($recipient, '503')) {
            return GatewayResponse::temporaryFailure('Email provider temporarily unavailable', 503);
        }

        return GatewayResponse::success(
            providerMessageId: 'email_'.Str::uuid()->toString(),
            delivered: true,
        );
    }
}
