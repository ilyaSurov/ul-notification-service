<?php

declare(strict_types=1);

return [
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),
    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'notifications'),
            'connection' => 'default',
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_USER', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [],
                'queue' => [
                    'job' => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
                    'prioritize' => true,
                    'max_priority' => 10,
                    'declare' => true,
                    'passive' => false,
                    'durable' => true,
                    'exclusive' => false,
                    'auto_delete' => false,
                    'arguments' => null,
                ],
            ],
            'worker' => env('RABBITMQ_WORKER', 'default'),
        ],
    ],
    'batching' => [
        'database' => env('DB_CONNECTION', 'pgsql'),
        'table' => 'job_batches',
    ],
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'null'),
    ],
];
