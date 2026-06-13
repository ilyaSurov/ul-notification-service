<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'channel' => NotificationChannel::Email,
            'message' => fake()->sentence(),
            'recipient' => fake()->safeEmail(),
            'priority' => NotificationType::Marketing->priority(),
            'status' => NotificationStatus::Queued,
            'idempotency_key' => null,
            'provider_message_id' => null,
            'attempts' => 0,
            'last_error' => null,
        ];
    }

    public function transactional(): self
    {
        return $this->state(fn (): array => [
            'priority' => NotificationType::Transactional->priority(),
        ]);
    }

    public function sms(): self
    {
        return $this->state(fn (): array => [
            'channel' => NotificationChannel::Sms,
            'recipient' => '+79001234567',
        ]);
    }
}
