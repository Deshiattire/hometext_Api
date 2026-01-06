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
     * Do NOT hardcode origins when using cookies.
     * Use pattern-based matching for all subdomains.
     */
    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '^https:\/\/.*\.hometexbangladesh\.org$',
        '^http:\/\/localhost(:[0-9]+)?$'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * Required for Sanctum cookie authentication
     */
    'supports_credentials' => true,
];
