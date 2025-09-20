<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for performance monitoring and optimization features.
    |
    */

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        'log_queries' => env('LOG_SLOW_QUERIES', true),
        'cache_metrics_ttl' => env('CACHE_METRICS_TTL', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Performance-related cache settings and TTL values.
    |
    */

    'cache' => [
        'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // 1 hour
        'long_ttl' => env('CACHE_LONG_TTL', 86400), // 24 hours
        'short_ttl' => env('CACHE_SHORT_TTL', 300), // 5 minutes
        
        'keys' => [
            'user_dashboard' => 'user_dashboard_{user_id}',
            'program_stats' => 'program_stats_{program_id}',
            'teacher_content' => 'teacher_{type}_{teacher_id}',
            'popular_quizzes' => 'popular_quizzes_{limit}',
            'system_stats' => 'system_stats',
            'active_languages' => 'active_languages',
            'guest_settings' => 'guest_settings',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for file storage and optimization.
    |
    */

    'files' => [
        'image_quality' => env('IMAGE_QUALITY', 85),
        'max_image_width' => env('MAX_IMAGE_WIDTH', 800),
        'max_image_height' => env('MAX_IMAGE_HEIGHT', 600),
        'thumbnail_size' => env('THUMBNAIL_SIZE', 150),
        
        'max_file_sizes' => [
            'image' => env('MAX_IMAGE_SIZE', 5 * 1024 * 1024), // 5MB
            'pdf' => env('MAX_PDF_SIZE', 10 * 1024 * 1024), // 10MB
            'quiz_file' => env('MAX_QUIZ_FILE_SIZE', 10 * 1024 * 1024), // 10MB
        ],
        
        'allowed_types' => [
            'images' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            'quiz_files' => ['application/pdf', 'text/plain', 'application/msword'],
        ],
        
        'cleanup' => [
            'enabled' => env('FILE_CLEANUP_ENABLED', true),
            'days_old' => env('FILE_CLEANUP_DAYS', 30),
            'schedule' => env('FILE_CLEANUP_SCHEDULE', 'daily'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Optimization
    |--------------------------------------------------------------------------
    |
    | Database performance and optimization settings.
    |
    */

    'database' => [
        'query_optimization' => [
            'eager_loading' => true,
            'select_specific_columns' => true,
            'use_indexes' => true,
            'limit_results' => true,
        ],
        
        'pagination' => [
            'default_per_page' => env('DEFAULT_PAGINATION_SIZE', 15),
            'max_per_page' => env('MAX_PAGINATION_SIZE', 100),
        ],
        
        'connection_pool' => [
            'max_connections' => env('DB_MAX_CONNECTIONS', 100),
            'idle_timeout' => env('DB_IDLE_TIMEOUT', 300),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Performance
    |--------------------------------------------------------------------------
    |
    | API-specific performance settings.
    |
    */

    'api' => [
        'rate_limiting' => [
            'enabled' => env('API_RATE_LIMITING_ENABLED', true),
            'requests_per_minute' => env('API_REQUESTS_PER_MINUTE', 60),
            'burst_limit' => env('API_BURST_LIMIT', 100),
        ],
        
        'response_caching' => [
            'enabled' => env('API_RESPONSE_CACHING_ENABLED', true),
            'ttl' => env('API_CACHE_TTL', 300), // 5 minutes
            'vary_by_user' => true,
        ],
        
        'compression' => [
            'enabled' => env('API_COMPRESSION_ENABLED', true),
            'min_size' => env('API_COMPRESSION_MIN_SIZE', 1024), // 1KB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Content Delivery Network settings for static assets.
    |
    */

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL'),
        'cache_control' => [
            'images' => 'max-age=31536000, public, immutable', // 1 year
            'files' => 'max-age=86400, public', // 1 day
            'api' => 'max-age=300, public', // 5 minutes
        ],
    ],

];