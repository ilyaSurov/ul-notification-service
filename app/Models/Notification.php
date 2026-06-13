<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'channel',
        'message',
        'recipient',
        'priority',
        'status',
        'idempotency_key',
        'provider_message_id',
        'attempts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'status' => NotificationStatus::class,
            'priority' => 'integer',
            'attempts' => 'integer',
        ];
    }
}
