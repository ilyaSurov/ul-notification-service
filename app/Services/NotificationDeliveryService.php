<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EmailGatewayInterface;
use App\Contracts\SmsGatewayInterface;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class NotificationDeliveryService
{
    public function __construct(
        private readonly SmsGatewayInterface $smsGateway,
        private readonly EmailGatewayInterface $emailGateway,
        private readonly StatusTrackerService $statusTracker,
        private readonly IdempotencyService $idempotencyService,
    ) {
    }

    public function deliver(Notification $notification): void
    {
        if (in_array($notification->status, [NotificationStatus::Sent, NotificationStatus::Delivered, NotificationStatus::Failed], true)) {
            Log::info('Notification already processed, skipping', [
                'notification_id' => $notification->id,
                'status' => $notification->status->value,
            ]);

            return;
        }

        if (! $this->idempotencyService->acquireProcessingLock($notification->id)) {
            return;
        }

        $this->statusTracker->incrementAttempts($notification);
        $notification->refresh();

        Log::info('Delivering notification', [
            'notification_id' => $notification->id,
            'channel' => $notification->channel->value,
            'attempt' => $notification->attempts,
        ]);

        $response = match ($notification->channel) {
            NotificationChannel::Sms => $this->smsGateway->send($notification->recipient, $notification->message),
            NotificationChannel::Email => $this->emailGateway->send($notification->recipient, $notification->message),
        };

        if ($response->success) {
            $this->statusTracker->markSent($notification, (string) $response->providerMessageId);

            if ($response->delivered) {
                $this->statusTracker->markDelivered($notification);
            }

            return;
        }

        if ($response->retryable) {
            $this->idempotencyService->releaseProcessingLock($notification->id);

            throw new \RuntimeException($response->errorMessage ?? 'Temporary gateway failure');
        }

        $this->statusTracker->markFailed($notification, (string) $response->errorMessage);
    }
}
