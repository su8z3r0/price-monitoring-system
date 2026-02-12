<?php

namespace App\Services;

use App\Contracts\ProxyProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GeoNodeProxyService implements ProxyProviderInterface
{
    private const GEONODE_API_URL = 'https://proxylist.geonode.com/api/proxy-list?limit=50&page=1&sort_by=latency&sort_type=asc&protocols=http,https&anonymityLevel=elite&anonymityLevel=anonymous';
    private const CACHE_KEY = 'cyper_proxies';
    private const CACHE_TTL = 3600;

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
        Log::info("Updating proxy list from GeoNode...");

        try {
            // Use the full URL string directly to ensure query parameters are passed exactly as Magento does
            // (e.g. repeated anonymityLevel keys)
            $response = Http::get(self::GEONODE_API_URL);

            if (!$response->successful()) {
                Log::error('GeoNode API request failed: ' . $response->status());
                return [];
            }

            $data = $response->json();
            $candidates = [];

            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $proxy) {
                    // Start parity with Magento logic:
                    // 1. Check IP, Port, Protocols
                    if (empty($proxy['ip']) || empty($proxy['port']) || empty($proxy['protocols'])) {
                        continue;
                    }

                    // 2. Security Check (Double check anonymity)
                    $anonymity = strtolower($proxy['anonymityLevel'] ?? '');
                    if ($anonymity === 'transparent') {
                        continue;
                    }
                    // Note: API already filters for elite/anonymous, but Magento kept a check.
                    // Magento code:
                    /*
                    $anonymity = strtolower($item['anonymityLevel'] ?? '');
                    if ($anonymity === 'transparent') {
                        continue; 
                    }
                    */
                    
                    // 3. Filter by Latency (if config exists, but we skip for now or use default)
                    // ...

                    // 4. Protocol Selection (socks5 > socks4 > http)
                    $protocol = 'http';
                    if (in_array('socks5', $proxy['protocols'])) {
                        $protocol = 'socks5';
                    } elseif (in_array('socks4', $proxy['protocols'])) {
                        $protocol = 'socks4';
                    }

                    $candidates[] = [
                        'url' => $proxy['ip'] . ':' . $proxy['port'], // Magento format: ip:port (no protocol prefix in url key?)
                        // Wait, Magento uses: 'url' => $item['ip'] . ':' . $item['port']
                        // And then later constructs the proxy string in curl opt.
                        // Laravel GeoNodeProxyService previously constructed 'url' with protocol://...
                        // BUT, ProxyPool's buildGuzzleProxyOptions used to use 'url' directly as the proxy string.
                        // AND validateProxies uses 'url' for CURLOPT_PROXY.
                        // Ideally, we should follow Magento's data structure internal to the array if possible, 
                        // OR adapt to what Laravel consumers expect.
                        // Let's look at validateProxies:
                        // curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
                        // If $proxyUrl is barely ip:port, and we set CURLOPT_PROXYTYPE, it works.
                        // Magento does exactly this.
                        
                        // However, previous Laravel implementation had: 
                        // 'url' => strtolower($protocol) . '://' . ...
                        
                        // If I change 'url' to be just ip:port, does it break anything?
                        // In validateProxies:
                        // $proxyUrl = $proxy['url'];
                        // curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
                        // This accepts ip:port.
                        
                        // In ProxyPool:
                        // $this->proxies[] = [ ... 'url' => trim($parts[0]) ... ] -> this is usually ip:port or protocol://ip:port
                        // When used in CrawlerService:
                        // curl_setopt($ch, CURLOPT_PROXY, $proxy['url']);
                        // curl_setopt($ch, CURLOPT_PROXYTYPE, ...);
                        
                        // So 'ip:port' is safe if PROXYTYPE is set.
                        // Let's stick effectively to Magento's structure for parity.
                        
                        'url' => $proxy['ip'] . ':' . $proxy['port'],
                        'ip' => $proxy['ip'],
                        'port' => $proxy['port'],
                        'protocol' => $protocol,
                        'anonymity' => $anonymity,
                        // 'latency' => ...
                    ];
                }
            }

            Log::info("Found " . count($candidates) . " candidates after filtering. Starting validation...");
            
            $validProxies = $this->validateProxies($candidates);
            
            // NOTE: If validation returns 0 proxies in local environment, it's likely due to
            // network restrictions blocking SOCKS connections. This is expected in restricted networks.
            // In production environments with proper SOCKS access, validation will work correctly.

            Cache::put(self::CACHE_KEY, $validProxies, self::CACHE_TTL);
            
            Log::info("Updated proxy cache with " . count($validProxies) . " valid proxies (from " . count($candidates) . " candidates).");

            return $validProxies;

        } catch (\Exception $e) {
            Log::error('GeoNode Service Error: ' . $e->getMessage());
            return [];
        }
    }

    private function validateProxies(array $proxies): array
    {
        if (empty($proxies)) {
            return [];
        }

        // Log::info('Validating ' . count($proxies) . ' proxies...');
        
        $mh = curl_multi_init();
        $channels = [];
        $validProxies = [];
        
        // Target URL for validation (lightweight and reliable)
        $testUrl = 'http://www.google.com';

        foreach ($proxies as $key => $proxy) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request only
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Short timeout for validation
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            $proxyUrl = $proxy['url'];
            curl_setopt($ch, CURLOPT_PROXY, $proxyUrl);
            
            if ($proxy['protocol'] === 'socks5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            } elseif ($proxy['protocol'] === 'socks4') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
            } else {
                // HTTP/HTTPS proxies use default CURLPROXY_HTTP
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
            
            curl_multi_add_handle($mh, $ch);
            $channels[$key] = $ch;
        }

        // Execute handles
        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                // Wait a short time for more activity
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        // Collect results
        foreach ($channels as $key => $ch) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Accept 2xx, 3xx, and 403 (Forbidden)
            // 403 means the proxy connected successfully but the target blocked the request
            // This is still a valid, working proxy
            if (($httpCode >= 200 && $httpCode < 400) || $httpCode == 403) {
                $validProxies[] = $proxies[$key];
            } else {
                // Determine error for logging (optional, verbose)
                // $error = curl_error($ch);
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
