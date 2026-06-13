<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendNotificationJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PriorityQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactional_jobs_are_dispatched_with_higher_priority(): void
    {
        Queue::fake();

        $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Marketing',
            'recipients' => ['marketing@example.com'],
            'type' => 'marketing',
        ]);

        $this->postJson('/api/v1/notifications/send', [
            'channel' => 'email',
            'message' => 'Transactional',
            'recipients' => ['txn@example.com'],
            'type' => 'transactional',
        ]);

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->queue === 'notifications' && $job->priority === 10;
        });

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->priority === 0;
        });
    }
}
