<?php

declare(strict_types=1);

namespace App\Gateways;

use App\Contracts\SmsGatewayInterface;
use App\DTO\GatewayResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockSmsGateway implements SmsGatewayInterface
{
    public function send(string $recipient, string $message): GatewayResponse
    {
        Log::info('Mock SMS gateway send', [
            'recipient' => $recipient,
            'message_length' => strlen($message),
        ]);

        if (str_starts_with($recipient, '+000')) {
            return GatewayResponse::permanentFailure('Invalid phone number', 400);
        }

        if (str_contains($recipient, '503')) {
            return GatewayResponse::temporaryFailure('SMS provider temporarily unavailable', 503);
        }

        return GatewayResponse::success(
            providerMessageId: 'sms_'.Str::uuid()->toString(),
            delivered: true,
        );
    }
}
