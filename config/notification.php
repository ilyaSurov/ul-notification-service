<?php

declare(strict_types=1);

return [
    'rate_limit_per_minute' => (int) env('NOTIFICATION_RATE_LIMIT', 60),
    'queue_name' => env('NOTIFICATION_QUEUE', 'notifications'),
    'idempotency_ttl_seconds' => (int) env('IDEMPOTENCY_TTL_SECONDS', 86400),
    'processing_lock_ttl_seconds' => (int) env('PROCESSING_LOCK_TTL_SECONDS', 86400),
    'max_retry_attempts' => (int) env('NOTIFICATION_MAX_RETRIES', 3),
];
