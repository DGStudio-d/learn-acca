<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the rate limiting settings for your application.
    | These settings will be used by the rate limiting middleware to control
    | the number of requests that can be made to your API endpoints.
    |
    */

    'api' => [
        'requests' => env('RATE_LIMIT_API_REQUESTS', 60),
        'per_minute' => env('RATE_LIMIT_API_PER_MINUTE', 1),
    ],

    'auth' => [
        'login' => [
            'requests' => env('RATE_LIMIT_LOGIN_REQUESTS', 5),
            'per_minute' => env('RATE_LIMIT_LOGIN_PER_MINUTE', 1),
        ],
        'register' => [
            'requests' => env('RATE_LIMIT_REGISTER_REQUESTS', 3),
            'per_minute' => env('RATE_LIMIT_REGISTER_PER_MINUTE', 1),
        ],
    ],

    'quiz' => [
        'attempts' => [
            'requests' => env('RATE_LIMIT_QUIZ_ATTEMPTS', 10),
            'per_minute' => env('RATE_LIMIT_QUIZ_PER_MINUTE', 1),
        ],
    ],

    'uploads' => [
        'requests' => env('RATE_LIMIT_UPLOAD_REQUESTS', 20),
        'per_minute' => env('RATE_LIMIT_UPLOAD_PER_MINUTE', 1),
    ],

    'guest' => [
        'requests' => env('RATE_LIMIT_GUEST_REQUESTS', 30),
        'per_minute' => env('RATE_LIMIT_GUEST_PER_MINUTE', 1),
    ],

];