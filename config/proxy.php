<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxy Rotation Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable proxy rotation for web scraping
    |
    */
    'enabled' => env('PROXY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Rotation Strategy
    |--------------------------------------------------------------------------
    |
    | Strategy for selecting proxies: 'round_robin' or 'random'
    |
    */
    'strategy' => env('PROXY_STRATEGY', 'round_robin'),

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    |
    | Maximum number of retry attempts with different proxies
    |
    */
    'max_retries' => env('PROXY_MAX_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Proxy Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for proxy connections
    |
    */
    'timeout' => env('PROXY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Proxy List
    |--------------------------------------------------------------------------
    |
    | List of proxy servers. Each proxy should have:
    | - url: The proxy server URL (e.g., http://proxy.example.com:8080)
    | - username: (optional) Proxy authentication username
    | - password: (optional) Proxy authentication password
    | - type: (optional) Proxy type: http, https, socks5 (default: http)
    |
    */
    'list' => array_filter([
        [
            'url' => env('PROXY_1_URL'),
            'username' => env('PROXY_1_USER'),
            'password' => env('PROXY_1_PASS'),
            'type' => env('PROXY_1_TYPE', 'http'),
        ],
        [
            'url' => env('PROXY_2_URL'),
            'username' => env('PROXY_2_USER'),
            'password' => env('PROXY_2_PASS'),
            'type' => env('PROXY_2_TYPE', 'http'),
        ],
        [
            'url' => env('PROXY_3_URL'),
            'username' => env('PROXY_3_USER'),
            'password' => env('PROXY_3_PASS'),
            'type' => env('PROXY_3_TYPE', 'http'),
        ],
    ], fn($proxy) => !empty($proxy['url'])),
];
