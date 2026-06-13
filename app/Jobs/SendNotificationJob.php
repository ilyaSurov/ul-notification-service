<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Notification;
use App\Services\NotificationDeliveryService;
use App\Services\StatusTrackerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $priority = 0;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(
        public readonly string $notificationId,
        int $priority = 0,
    ) {
        $this->priority = $priority;
        $this->onQueue((string) config('notification.queue_name', 'notifications'));
    }

    public function handle(
        NotificationDeliveryService $deliveryService,
        StatusTrackerService $statusTracker,
    ): void {
        $notification = Notification::query()->find($this->notificationId);

        if ($notification === null) {
            Log::warning('Notification not found for job', [
                'notification_id' => $this->notificationId,
            ]);

            return;
        }

        try {
            $deliveryService->deliver($notification);
        } catch (\Throwable $exception) {
            if ($this->attempts() >= $this->tries) {
                $statusTracker->markFailed(
                    $notification->fresh() ?? $notification,
                    $exception->getMessage(),
                );

                Log::error('Notification delivery failed after retries', [
                    'notification_id' => $notification->id,
                    'error' => $exception->getMessage(),
                ]);

                return;
            }

            throw $exception;
        }
    }
}
