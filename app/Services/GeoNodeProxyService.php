<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoNodeProxyService
{
    private const GEONODE_API_URL = 'https://proxylist.geonode.com/api/proxy-list';
    private const CACHE_KEY = 'cyper_proxies';
    private const CACHE_TTL = 3600; // 1 hour

    public function getProxies(): array
    {
        $cached = \Illuminate\Support\Facades\Cache::get(self::CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        return $this->updateProxies();
    }

    public function updateProxies(): array
    {
        $limit = 50;
        Log::info("Fetching fresh proxies from GeoNode (limit: {$limit})...");

        try {
            $response = Http::get(self::GEONODE_API_URL, [
                'limit' => $limit,
                'page' => 1,
                'sort_by' => 'lastChecked',
                'sort_type' => 'desc',
                'protocols' => 'http,https', // Guzzle typically handles http/https proxies best
            ]);

            if (!$response->successful()) {
                Log::error('GeoNode API failed: ' . $response->status());
                return [];
            }

            $data = $response->json();
            $candidates = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $proxy) {
                    $protocol = $proxy['protocols'][0] ?? 'http';
                    $candidates[] = [
                        'url' => strtolower($protocol) . '://' . $proxy['ip'] . ':' . $proxy['port'],
                        'ip' => $proxy['ip'],
                        'port' => $proxy['port'],
                        'protocol' => $protocol,
                    ];
                }
            }

            Log::info("Found " . count($candidates) . " candidates. Validating...");

            $validProxies = $this->validateProxies($candidates);

            \Illuminate\Support\Facades\Cache::put(self::CACHE_KEY, $validProxies, self::CACHE_TTL);
            
            Log::info("Stored " . count($validProxies) . " valid proxies in cache.");

            return $validProxies;

        } catch (\Exception $e) {
            Log::error('GeoNode Service Error: ' . $e->getMessage());
            return [];
        }
    }

    private function validateProxies(array $proxies): array
    {
        $valid = [];
        $client = new \GuzzleHttp\Client([
            'timeout' => 5,
            'connect_timeout' => 3,
        ]);

        $requests = function ($proxies) {
            foreach ($proxies as $config) {
                yield $config['url'] => new \GuzzleHttp\Psr7\Request('GET', 'http://www.google.com', [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]);
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests($proxies), [
            'concurrency' => 10,
            'fulfilled' => function ($response, $proxyUrl) use (&$valid, $proxies) {
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
                    // Find original config
                    foreach ($proxies as $p) {
                        if ($p['url'] === $proxyUrl) {
                            $valid[] = $p;
                            break;
                        }
                    }
                }
            },
            'rejected' => function ($reason, $proxyUrl) {
                // Log::debug("Proxy failed validation: $proxyUrl");
            },
            'options' => function ($request) use ($proxies) {
                 // Map request to proxy option
                 // Note: The key in 'yield' is passed as index/key, but not directly accessible easily in options closure in older guzzle versions without some state.
                 // Actually, simpler way: define Closures that capture the proxy config.
                 return [];
            }
        ]);
        
        // Re-implement simplified synchronous parallel validation because mapping dynamic options in Pool with simpler array keys is tricky without extra class overhead.
        // Let's use Http::pool which is Laravel native and cleaner.
        
        $responses = Http::pool(fn (\Illuminate\Http\Client\Pool $pool) => 
            array_map(fn ($proxy) => 
                $pool->as($proxy['url'])
                     ->withOptions(['proxy' => $proxy['url']])
                     ->timeout(5)
                     ->get('http://www.google.com'),
                $proxies
            )
        );

        foreach ($responses as $url => $response) {
            if ($response instanceof \Illuminate\Http\Client\Response && $response->successful()) {
                // Find matching proxy config
                foreach ($proxies as $p) {
                    if ($p['url'] === $url) {
                        $valid[] = $p;
                        break;
                    }
                }
            }
        }

        return $valid;
    }

    public function removeProxy(string $proxyUrl): void
    {
        $proxies = \Illuminate\Support\Facades\Cache::get(self::CACHE_KEY, []);
        
        $originalCount = count($proxies);
        $proxies = array_filter($proxies, fn($p) => $p['url'] !== $proxyUrl);

        if (count($proxies) < $originalCount) {
            \Illuminate\Support\Facades\Cache::put(self::CACHE_KEY, array_values($proxies), self::CACHE_TTL);
            Log::info("Removed failed proxy from cache: $proxyUrl");
        }
    }
}
