<?php

return [
    'env' => env('APP_ENV', 'production'),
    
    'optimization' => [
        'cache_config' => true,
        'cache_routes' => true,
        'cache_views' => true,
        'cache_events' => true,
    ],
    
    'monitoring' => [
        'sentry_dsn' => env('SENTRY_LARAVEL_DSN'),
        'telescope_enabled' => env('TELESCOPE_ENABLED', false),
        'horizon_enabled' => env('HORIZON_ENABLED', false),
    ],
    
    'backup' => [
        'enabled' => true,
        'schedule' => 'daily',
        'retention_days' => 30,
        'include_files' => [
            'storage/app/incidents',
            'storage/app/public',
        ],
        'exclude_tables' => [
            'telescope_entries',
            'telescope_entries_tags',
            'telescope_monitoring',
        ],
    ],
];