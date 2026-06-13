<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetryFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_permanent_gateway_failure_marks_notification_failed(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Will fail',
            'recipients' => ['bad@invalid.test'],
        ]);

        $response->assertStatus(202);
        $notificationId = $response->json('data.notifications.0.id');

        $this->assertDatabaseHas('notifications', [
            'id' => $notificationId,
            'status' => NotificationStatus::Failed->value,
        ]);
    }
}
