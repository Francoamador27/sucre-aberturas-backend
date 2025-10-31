<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // ⚠️ NO usar '*' si supports_credentials es true
    'allowed_origins' => [
        'https://decoimanes.com',
        'https://www.decoimanes.com',
        'http://localhost:5173',
        'https://dentalcoresoftware.test',
        
        'https://localhost:5173',
        'http://localhost:5174'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Esto permite el uso de cookies y withCredentials
    'supports_credentials' => true,

];
