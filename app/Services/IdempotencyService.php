<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IdempotencyService
{
    private const SEND_KEY_PREFIX = 'idempotency:send:';

    private const PROCESSED_KEY_PREFIX = 'notification:processed:';

    public function storeSendResult(string $idempotencyKey, array $payload): void
    {
        $ttl = (int) config('notification.idempotency_ttl_seconds', 86400);

        Cache::put(
            self::SEND_KEY_PREFIX.$idempotencyKey,
            $payload,
            $ttl,
        );

        Log::info('Stored idempotency key', ['idempotency_key' => $idempotencyKey]);
    }

    public function getSendResult(string $idempotencyKey): ?array
    {
        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get(self::SEND_KEY_PREFIX.$idempotencyKey);

        return $cached;
    }

    public function acquireProcessingLock(string $notificationId): bool
    {
        $ttl = (int) config('notification.processing_lock_ttl_seconds', 86400);
        $key = self::PROCESSED_KEY_PREFIX.$notificationId;

        if (! Cache::add($key, '1', $ttl)) {
            Log::warning('Duplicate queue message skipped', [
                'notification_id' => $notificationId,
            ]);

            return false;
        }

        return true;
    }

    public function releaseProcessingLock(string $notificationId): void
    {
        Cache::forget(self::PROCESSED_KEY_PREFIX.$notificationId);
    }
}
