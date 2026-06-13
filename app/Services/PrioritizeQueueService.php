<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;

class PrioritizeQueueService
{
    public function resolvePriority(?string $type): int
    {
        $notificationType = NotificationType::tryFrom((string) $type) ?? NotificationType::Marketing;
        $priority = $notificationType->priority();

        Log::debug('Resolved notification priority', [
            'type' => $notificationType->value,
            'priority' => $priority,
        ]);

        return $priority;
    }

    public function resolveQueueName(): string
    {
        return (string) config('notification.queue_name', 'notifications');
    }
}
