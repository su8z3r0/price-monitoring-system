<?php

namespace App\Services;

use App\Contracts\ProxyProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProxiflyProxyService implements ProxyProviderInterface
{
    private const PROXIFLY_API_URL = 'https://cdn.jsdelivr.net/gh/proxifly/free-proxy-list@main/proxies/protocols/http/data.json';
    private const CACHE_KEY = 'proxifly_proxies';
    private const CACHE_TTL = 1800; // 30 minutes (Proxifly updates every 5 min)

    public function getProxies(): array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        return $this->updateProxies();
    }

    public function updateProxies(): array
    {
        Log::info("Updating proxy list from Proxifly...");

        try {
            // Use HTTP-only endpoint for better compatibility
            $response = Http::timeout(30)->get(self::PROXIFLY_API_URL);

            if (!$response->successful()) {
                Log::error('Proxifly API request failed: ' . $response->status());
                return [];
            }

            $data = $response->json();
            $candidates = [];

            if (is_array($data)) {
                foreach ($data as $proxy) {
                    // Skip if missing required fields
                    if (empty($proxy['ip']) || empty($proxy['port'])) {
                        continue;
                    }

                    // Skip transparent proxies (not anonymous)
                    $anonymity = strtolower($proxy['anonymity'] ?? '');
                    if ($anonymity === 'transparent') {
                        continue;
                    }

                    $protocol = strtolower($proxy['protocol'] ?? 'http');
                    
                    // Only use HTTP/HTTPS for Devilbox compatibility
                    if (!in_array($protocol, ['http', 'https'])) {
                        continue;
                    }

                    $candidates[] = [
                        'url' => $proxy['ip'] . ':' . $proxy['port'],
                        'ip' => $proxy['ip'],
                        'port' => $proxy['port'],
                        'protocol' => $protocol,
                        'anonymity' => $anonymity,
                        'country' => $proxy['geolocation']['country'] ?? 'unknown',
                    ];
                }
            }

            Log::info("Found " . count($candidates) . " HTTP candidates from Proxifly. Starting validation...");
            
            $validProxies = $this->validateProxies($candidates);
            
            Cache::put(self::CACHE_KEY, $validProxies, self::CACHE_TTL);
            
            Log::info("Updated Proxifly cache with " . count($validProxies) . " valid proxies (from " . count($candidates) . " candidates).");

            return $validProxies;

        } catch (\Exception $e) {
            Log::error('Proxifly Service Error: ' . $e->getMessage());
            return [];
        }
    }

    private function validateProxies(array $proxies): array
    {
        if (empty($proxies)) {
            return [];
        }

        $mh = curl_multi_init();
        $channels = [];
        $validProxies = [];
        
        $testUrl = 'http://www.google.com';

        // Limit validation to first 100 for speed
        $proxiesToValidate = array_slice($proxies, 0, 100);

        foreach ($proxiesToValidate as $key => $proxy) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            $proxyUrl = $proxy['url'];
            curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            
            curl_multi_add_handle($mh, $ch);
            $channels[$key] = $ch;
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($channels as $key => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Accept 2xx, 3xx, and 403 (proxy works but target blocks)
            if (($httpCode >= 200 && $httpCode < 400) || $httpCode == 403) {
                $validProxies[] = $proxiesToValidate[$key];
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        
        return $validProxies;
    }

    public function removeProxy(string $proxyUrl): void
    {
        $proxies = Cache::get(self::CACHE_KEY, []);
        
        $originalCount = count($proxies);
        $proxies = array_filter($proxies, fn($p) => $p['url'] !== $proxyUrl);

        if (count($proxies) < $originalCount) {
            Cache::put(self::CACHE_KEY, array_values($proxies), self::CACHE_TTL);
        }
    }
}
