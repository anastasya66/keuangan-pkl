<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration - FinToko
    |--------------------------------------------------------------------------
    |
    | Mengatur hak akses frontend React terhadap API backend Laravel.
    | Mendukung origin Vite development lokal (port 5173) secara aman tanpa wildcard.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Authorization', 'Content-Type', 'X-Requested-With', 'Accept'],

    'exposed_headers' => ['Authorization', 'Content-Disposition'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
