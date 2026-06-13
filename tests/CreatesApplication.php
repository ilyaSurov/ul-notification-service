<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        if (! file_exists(__DIR__.'/../.env') && file_exists(__DIR__.'/../.env.example')) {
            copy(__DIR__.'/../.env.example', __DIR__.'/../.env');
        }

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}
