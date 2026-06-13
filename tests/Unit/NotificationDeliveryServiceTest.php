<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Contracts\EmailGatewayInterface;
use App\DTO\GatewayResponse;
use App\Enums\NotificationChannel;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\IdempotencyService;
use App\Services\NotificationDeliveryService;
use App\Services\StatusTrackerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class NotificationDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivers_email_successfully(): void
    {
        $gateway = Mockery::mock(EmailGatewayInterface::class);
        $gateway->shouldReceive('send')
            ->once()
            ->andReturn(GatewayResponse::success('email-123', true));

        $idempotency = Mockery::mock(IdempotencyService::class);
        $idempotency->shouldReceive('acquireProcessingLock')->once()->andReturn(true);

        $this->app->instance(EmailGatewayInterface::class, $gateway);
        $this->app->instance(IdempotencyService::class, $idempotency);

        $notification = Notification::factory()->create([
            'channel' => NotificationChannel::Email,
            'status' => NotificationStatus::Queued,
        ]);

        app(NotificationDeliveryService::class)->deliver($notification);
        $notification->refresh();

        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame('email-123', $notification->provider_message_id);
    }

    public function test_marks_permanent_failure(): void
    {
        $gateway = Mockery::mock(EmailGatewayInterface::class);
        $gateway->shouldReceive('send')
            ->once()
            ->andReturn(GatewayResponse::permanentFailure('Invalid email'));

        $idempotency = Mockery::mock(IdempotencyService::class);
        $idempotency->shouldReceive('acquireProcessingLock')->once()->andReturn(true);

        $this->app->instance(EmailGatewayInterface::class, $gateway);
        $this->app->instance(IdempotencyService::class, $idempotency);

        $notification = Notification::factory()->create([
            'channel' => NotificationChannel::Email,
            'recipient' => 'bad@invalid.test',
            'status' => NotificationStatus::Queued,
        ]);

        app(NotificationDeliveryService::class)->deliver($notification);
        $notification->refresh();

        $this->assertSame(NotificationStatus::Failed, $notification->status);
        $this->assertSame('Invalid email', $notification->last_error);
    }

    public function test_throws_on_retryable_failure(): void
    {
        $gateway = Mockery::mock(EmailGatewayInterface::class);
        $gateway->shouldReceive('send')
            ->once()
            ->andReturn(GatewayResponse::temporaryFailure('Provider unavailable', 503));

        $idempotency = Mockery::mock(IdempotencyService::class);
        $idempotency->shouldReceive('acquireProcessingLock')->once()->andReturn(true);
        $idempotency->shouldReceive('releaseProcessingLock')->once();

        $this->app->instance(EmailGatewayInterface::class, $gateway);
        $this->app->instance(IdempotencyService::class, $idempotency);

        $notification = Notification::factory()->create([
            'channel' => NotificationChannel::Email,
            'recipient' => 'retry503@example.com',
            'status' => NotificationStatus::Queued,
        ]);

        $this->expectException(\RuntimeException::class);

        app(NotificationDeliveryService::class)->deliver($notification);
    }
}
