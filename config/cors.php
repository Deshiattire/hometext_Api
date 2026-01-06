<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Dynamically allow all subdomains of hometexbangladesh.org
     */
    'allowed_origins' => function ($origin) {
        if (!$origin) return false;

        return str_ends_with($origin, '.hometexbangladesh.org')
            || $origin === 'https://hometexbangladesh.org'
            || str_starts_with($origin, 'http://localhost');
    },

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * Required for Sanctum cookie authentication
     */
    'supports_credentials' => true,
];
