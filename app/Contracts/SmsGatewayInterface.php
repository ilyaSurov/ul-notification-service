<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\GatewayResponse;

interface SmsGatewayInterface
{
    public function send(string $recipient, string $message): GatewayResponse;
}
