<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prometheus Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Prometheus metrics collection and export.
    |
    */

    'enabled' => env('PROMETHEUS_ENABLED', true),

    'namespace' => env('PROMETHEUS_NAMESPACE', 'laravel'),

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('PROMETHEUS_REDIS_DATABASE', 2),
        'timeout' => 0.1,
        'read_timeout' => 10,
        'persistent_connections' => false,
    ],

    'collectors' => [
        'http_requests' => true,
        'database' => true,
        'redis' => true,
        'queue' => true,
        'memory' => true,
        'application_info' => true,
    ],

    'route_patterns' => [
        // Patterns to group similar routes together
        '/api/users/{id}' => '/api/users/{id}',
        '/api/posts/{id}' => '/api/posts/{id}',
        // Add more patterns as needed
    ],

    'ignored_routes' => [
        '/metrics',
        '/health',
        '/health/ready',
        '/health/live',
        '/up',
    ],
];