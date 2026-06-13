<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendNotificationService
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly PrioritizeQueueService $prioritizeQueueService,
    ) {
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array<string, mixed>
     */
    public function send(
        NotificationChannel $channel,
        string $message,
        array $recipients,
        ?string $type = null,
        ?string $idempotencyKey = null,
    ): array {
        if ($idempotencyKey !== null) {
            $cached = $this->idempotencyService->getSendResult($idempotencyKey);

            if ($cached !== null) {
                Log::info('Returning cached idempotent response', [
                    'idempotency_key' => $idempotencyKey,
                ]);

                return array_merge($cached, ['duplicate' => true]);
            }
        }

        $priority = $this->prioritizeQueueService->resolvePriority($type);
        $queueName = $this->prioritizeQueueService->resolveQueueName();
        $batchId = Str::uuid()->toString();

        $notifications = DB::transaction(function () use (
            $channel,
            $message,
            $recipients,
            $priority,
            $idempotencyKey,
            $batchId,
            $queueName,
        ): array {
            $created = [];

            foreach ($recipients as $recipient) {
                $notification = Notification::query()->create([
                    'channel' => $channel,
                    'message' => $message,
                    'recipient' => $recipient,
                    'priority' => $priority,
                    'status' => NotificationStatus::Queued,
                    'idempotency_key' => $idempotencyKey,
                    'attempts' => 0,
                ]);

                SendNotificationJob::dispatch($notification->id, $priority)
                    ->onQueue($queueName);

                $created[] = [
                    'id' => $notification->id,
                    'recipient' => $notification->recipient,
                    'status' => $notification->status->value,
                    'priority' => $notification->priority,
                ];

                Log::info('Notification queued', [
                    'notification_id' => $notification->id,
                    'batch_id' => $batchId,
                    'priority' => $priority,
                ]);
            }

            return $created;
        });

        $response = [
            'batch_id' => $batchId,
            'notifications' => $notifications,
        ];

        if ($idempotencyKey !== null) {
            $this->idempotencyService->storeSendResult($idempotencyKey, $response);
        }

        return $response;
    }
}
