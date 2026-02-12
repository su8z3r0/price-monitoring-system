<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Proxy System Enable/Disable
    |--------------------------------------------------------------------------
    |
    | Set to false to disable proxy usage entirely and make direct requests.
    | This is faster and more reliable if your IP is not blocked by target sites.
    |
    */
    'enabled' => env('PROXY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Proxy Providers
    |--------------------------------------------------------------------------
    |
    | Configure which proxy providers to use. Multiple providers can be enabled
    | simultaneously - their proxies will be combined into a single pool.
    |
    | Available providers:
    | - geonode: GeoNode free proxy API (~50 proxies, HTTP/HTTPS)
    | - proxifly: Proxifly free proxy list (~1600 HTTP proxies, updated every 5 min)
    | - freeproxy24: FreeProxy24 API (~7960 proxies, various protocols)
    | - manual: Use manually configured proxy list below
    |
    */
    'providers' => [
        'geonode' => [
            'enabled' => env('PROXY_PROVIDER_GEONODE_ENABLED', true),
            'url' => 'https://proxylist.geonode.com/api/proxy-list?limit=50&page=1&sort_by=latency&sort_type=asc&protocols=http,https&anonymityLevel=elite&anonymityLevel=anonymous',
        ],
        'proxifly' => [
            'enabled' => env('PROXY_PROVIDER_PROXIFLY_ENABLED', true),
            'url' => 'https://cdn.jsdelivr.net/gh/proxifly/free-proxy-list@main/proxies/protocols/http/data.json',
        ],
        'freeproxy24' => [
            'enabled' => env('PROXY_PROVIDER_FREEPROXY24_ENABLED', false), // Disabled by default (too many, slower validation)
            'url' => 'https://freeproxy24.com/api/free-proxy-list?limit=500&page=1&sortBy=lastChecked&sortType=desc',
        ],
        'manual' => [
            'enabled' => env('PROXY_PROVIDER_MANUAL_ENABLED', false),
            'list' => env('PROXY_LIST', [
                // Add your premium proxies here
                // Example: 'premium-proxy.example.com:8080',
            ]),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Validation & Retry Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('PROXY_TIMEOUT', 30), // seconds
    'max_retries' => env('PROXY_MAX_RETRIES', 8), // Number of retry attempts with different proxies
    'validation_limit' => env('PROXY_VALIDATION_LIMIT', 100), // Max proxies to validate per provider
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => env('PROXY_CACHE_TTL', 3600), // 1 hour in seconds
    
    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    */
    'user_agent' => env('PROXY_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'),
];
