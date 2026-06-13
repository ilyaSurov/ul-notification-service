<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\IdempotencyService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IdempotencyServiceTest extends TestCase
{
    public function test_stores_and_retrieves_send_result(): void
    {
        $service = new IdempotencyService();
        $service->storeSendResult('test-key', ['batch_id' => 'batch-1']);

        $result = $service->getSendResult('test-key');

        $this->assertSame(['batch_id' => 'batch-1'], $result);
    }

    public function test_acquire_processing_lock(): void
    {
        $service = new IdempotencyService();

        $this->assertTrue($service->acquireProcessingLock('uuid-1'));
        $this->assertFalse($service->acquireProcessingLock('uuid-1'));
    }

    public function test_release_processing_lock_allows_reacquire(): void
    {
        $service = new IdempotencyService();

        $this->assertTrue($service->acquireProcessingLock('uuid-2'));
        $service->releaseProcessingLock('uuid-2');
        $this->assertTrue($service->acquireProcessingLock('uuid-2'));
    }
}
