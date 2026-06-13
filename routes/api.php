<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('notifications/send', [NotificationController::class, 'send']);
    Route::get('notifications/history', [NotificationController::class, 'history']);
    Route::get('notifications/{id}/status', [NotificationController::class, 'status']);
});
