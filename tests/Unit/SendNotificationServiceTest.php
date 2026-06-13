<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\IdempotencyService;
use App\Services\PrioritizeQueueService;
use App\Services\SendNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SendNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_notifications_and_dispatches_jobs(): void
    {
        Queue::fake();

        $service = app(SendNotificationService::class);

        $result = $service->send(
            channel: NotificationChannel::Email,
            message: 'Hello',
            recipients: ['one@example.com', 'two@example.com'],
            type: 'transactional',
        );

        $this->assertArrayHasKey('batch_id', $result);
        $this->assertCount(2, $result['notifications']);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'recipient' => 'one@example.com',
            'status' => NotificationStatus::Queued->value,
            'priority' => 10,
        ]);

        Queue::assertPushed(\App\Jobs\SendNotificationJob::class, 2);
    }

    public function test_returns_cached_response_for_duplicate_idempotency_key(): void
    {
        $idempotency = Mockery::mock(IdempotencyService::class);
        $idempotency->shouldReceive('getSendResult')
            ->once()
            ->with('dup-key')
            ->andReturn(['batch_id' => 'existing-batch', 'notifications' => []]);
        $idempotency->shouldReceive('storeSendResult')->never();

        $this->app->instance(IdempotencyService::class, $idempotency);

        $service = new SendNotificationService($idempotency, new PrioritizeQueueService());

        $result = $service->send(
            channel: NotificationChannel::Email,
            message: 'Hello',
            recipients: ['one@example.com'],
            idempotencyKey: 'dup-key',
        );

        $this->assertTrue($result['duplicate']);
        $this->assertSame('existing-batch', $result['batch_id']);
    }
}
