<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StatusTrackerService
{
    public function markQueued(Notification $notification): Notification
    {
        return $this->updateStatus($notification, NotificationStatus::Queued);
    }

    public function markSent(Notification $notification, string $providerMessageId): Notification
    {
        $notification->provider_message_id = $providerMessageId;

        return $this->updateStatus($notification, NotificationStatus::Sent);
    }

    public function markDelivered(Notification $notification): Notification
    {
        return $this->updateStatus($notification, NotificationStatus::Delivered);
    }

    public function markFailed(Notification $notification, string $errorMessage): Notification
    {
        $notification->last_error = $errorMessage;

        return $this->updateStatus($notification, NotificationStatus::Failed);
    }

    public function incrementAttempts(Notification $notification): Notification
    {
        $notification->increment('attempts');
        $notification->refresh();

        Log::info('Notification attempt incremented', [
            'notification_id' => $notification->id,
            'attempts' => $notification->attempts,
        ]);

        return $notification;
    }

    public function getStatus(string $notificationId): ?Notification
    {
        return Notification::query()->find($notificationId);
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getHistoryForSubscriber(string $subscriberId): Collection
    {
        return Notification::query()
            ->where('recipient', $subscriberId)
            ->orderByDesc('created_at')
            ->get();
    }

    private function updateStatus(Notification $notification, NotificationStatus $status): Notification
    {
        $notification->status = $status;
        $notification->save();

        Log::info('Notification status updated', [
            'notification_id' => $notification->id,
            'status' => $status->value,
        ]);

        return $notification;
    }
}
