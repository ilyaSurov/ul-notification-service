<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\EmailGatewayInterface;
use App\Contracts\SmsGatewayInterface;
use App\Gateways\MockEmailGateway;
use App\Gateways\MockSmsGateway;
use App\Services\IdempotencyService;
use App\Services\PrioritizeQueueService;
use App\Services\SendNotificationService;
use App\Services\StatusTrackerService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGatewayInterface::class, MockSmsGateway::class);
        $this->app->singleton(EmailGatewayInterface::class, MockEmailGateway::class);
        $this->app->singleton(IdempotencyService::class);
        $this->app->singleton(PrioritizeQueueService::class);
        $this->app->singleton(StatusTrackerService::class);
        $this->app->singleton(SendNotificationService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('notification.rate_limit_per_minute', 60))
                ->by($request->ip());
        });
    }
}
