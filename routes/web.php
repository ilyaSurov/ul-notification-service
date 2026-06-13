<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'ul-notification-service',
        'version' => '1.0.0',
        'documentation' => url('/api/documentation'),
    ]);
});
