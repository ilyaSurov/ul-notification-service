<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\StatusTrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusTrackerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_notification_statuses(): void
    {
        $notification = Notification::factory()->create([
            'status' => NotificationStatus::Queued,
        ]);

        $service = app(StatusTrackerService::class);

        $service->markSent($notification, 'provider-123');
        $notification->refresh();

        $this->assertSame(NotificationStatus::Sent, $notification->status);
        $this->assertSame('provider-123', $notification->provider_message_id);

        $service->markDelivered($notification);
        $notification->refresh();
        $this->assertSame(NotificationStatus::Delivered, $notification->status);

        $service->markFailed($notification, 'timeout');
        $notification->refresh();
        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame('timeout', $notification->last_error);
    }

    public function test_returns_subscriber_history(): void
    {
        Notification::factory()->count(2)->create(['recipient' => 'user@example.com']);
        Notification::factory()->create(['recipient' => 'other@example.com']);

        $history = app(StatusTrackerService::class)->getHistoryForSubscriber('user@example.com');

        $this->assertCount(2, $history);
    }
}
