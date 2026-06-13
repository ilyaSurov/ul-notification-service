<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_idempotency_key_returns_conflict(): void
    {
        $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Hello',
            'recipients' => ['first@example.com'],
            'idempotency_key' => 'same-key',
        ])->assertStatus(202);

        $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Hello again',
            'recipients' => ['second@example.com'],
            'idempotency_key' => 'same-key',
        ])->assertStatus(409)
            ->assertJsonStructure(['message', 'data' => ['batch_id', 'notifications']]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_successful_send_with_idempotency_key(): void
    {
        $response = $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Hello',
            'recipients' => ['new@example.com'],
            'idempotency_key' => 'new-key',
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseHas('notifications', [
            'recipient' => 'new@example.com',
            'status' => NotificationStatus::Delivered->value,
        ]);
    }
}
