<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_endpoint_creates_queued_notifications(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Integration test message',
            'recipients' => ['client@example.com'],
            'type' => 'transactional',
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('data.notifications.0.status', 'queued');

        $this->assertDatabaseHas('notifications', [
            'recipient' => 'client@example.com',
            'status' => NotificationStatus::Queued->value,
            'priority' => 10,
        ]);

        Queue::assertPushed(SendNotificationJob::class);
    }

    public function test_full_delivery_cycle_updates_status(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Deliver me',
            'recipients' => ['delivered@example.com'],
        ]);

        $response->assertStatus(202);
        $notificationId = $response->json('data.notifications.0.id');

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'status' => NotificationStatus::Delivered->value,
        ]);
    }

    public function test_status_endpoint_returns_notification(): void
    {
        $notification = Notification::factory()->create([
            'recipient' => 'status@example.com',
            'status' => NotificationStatus::Sent,
            'provider_message_id' => 'provider-1',
        ]);

        $this->getJson("/api/v1/notifications/{$notification->id}/status")
            ->assertOk()
            ->assertJsonPath('data.status', 'sent')
            ->assertJsonPath('data.provider_message_id', 'provider-1');
    }

    public function test_history_endpoint_returns_subscriber_notifications(): void
    {
        Notification::factory()->count(2)->create(['recipient' => '+79001112233']);

        $this->getJson('/api/v1/notifications/history?subscriber_id='.urlencode('+79001112233'))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_validation_rejects_invalid_payload(): void
    {
        $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => '',
            'recipients' => ['not-an-email'],
        ])->assertStatus(422);
    }
}
