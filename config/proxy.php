<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxy List & Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can define a static list of proxies to be used by the ProxyPool
    | service, as well as configuration for the scraper (timeout, retries).
    |
    */

    'list' => [
        // 'http://username:password@ip:port',
    ],

    'timeout' => 30, // seconds
    'max_retries' => 3,
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
];
