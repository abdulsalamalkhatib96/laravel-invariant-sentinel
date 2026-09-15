<?php

return [
    'enabled' => env('SENTINEL_ENABLED', true),
    'service' => env('SENTINEL_SERVICE', env('APP_NAME', 'app')),
    'environment' => env('APP_ENV', 'production'),

    // Explicit registration is deterministic and recommended for production.
    'invariants' => [],

    'queue' => [
        'connection' => env('SENTINEL_QUEUE_CONNECTION'),
        'queue' => env('SENTINEL_QUEUE', 'sentinel'),
        'drain_batch' => 100,
    ],

    'storage' => [
        'connection' => env('SENTINEL_DB_CONNECTION'),
    ],

    'locks' => [
        'store' => env('SENTINEL_LOCK_STORE'),
        'seconds' => 30,
        'wait_seconds' => 2,
    ],

    'evaluation' => [
        'max_queries' => 50,
        'max_duration_ms' => 3000,
        'max_external_calls' => 0,
    ],

    'sweep' => [
        'enabled' => true,
        'chunk_size' => 500,
    ],

    'scheduler' => [
        // Off by default to avoid surprising host applications. Enable to honor SweepTrigger cron definitions.
        'enabled' => false,
        'drain_cron' => '* * * * *',
        'prune_cron' => '17 3 * * *',
    ],

    'evidence' => [
        'store_passes' => false,
        'store_failures' => true,
        'retention_days' => 30,
        'redact' => [
            'password', 'password_confirmation', 'token', 'access_token',
            'refresh_token', 'authorization', 'secret', 'api_key', 'api_secret',
            'card_number', 'cvv', 'cvc',
        ],
    ],

    'incidents' => [
        'auto_resolve' => true,
    ],

    'alerts' => [
        'log' => true,
    ],

    'remediation' => [
        'enabled' => false,
        'automatic' => false,
    ],

    'dashboard' => [
        'enabled' => env('SENTINEL_DASHBOARD', false),
        'path' => 'sentinel',
        'middleware' => ['web', 'auth', 'can:viewSentinel'],
    ],
];
