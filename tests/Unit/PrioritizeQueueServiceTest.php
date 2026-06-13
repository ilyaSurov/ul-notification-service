<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\NotificationType;
use App\Services\PrioritizeQueueService;
use Tests\TestCase;

class PrioritizeQueueServiceTest extends TestCase
{
    public function test_resolves_transactional_priority(): void
    {
        $service = new PrioritizeQueueService();

        $this->assertSame(10, $service->resolvePriority(NotificationType::Transactional->value));
    }

    public function test_resolves_marketing_priority_by_default(): void
    {
        $service = new PrioritizeQueueService();

        $this->assertSame(0, $service->resolvePriority(null));
        $this->assertSame(0, $service->resolvePriority(NotificationType::Marketing->value));
    }

    public function test_resolves_queue_name_from_config(): void
    {
        config(['notification.queue_name' => 'notifications']);

        $service = new PrioritizeQueueService();

        $this->assertSame('notifications', $service->resolveQueueName());
    }
}
