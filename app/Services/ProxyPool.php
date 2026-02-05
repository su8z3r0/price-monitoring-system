<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ProxyPool
{
    private array $proxies = [];
    private array $failedProxies = [];
    private int $currentIndex = 0;

    public function __construct()
    {
        $this->loadProxies();
    }

    /**
     * Load proxies from configuration
     */
    private function loadProxies(): void
    {
        $this->proxies = config('proxy.list', []);
        Log::info('ProxyPool loaded ' . count($this->proxies) . ' proxies');
    }

    /**
     * Get all available proxies (excluding failed ones)
     */
    public function getAvailableProxies(): array
    {
        return array_filter($this->proxies, function($proxy) {
            return !in_array($proxy['url'], $this->failedProxies);
        });
    }

    /**
     * Get next proxy using round-robin strategy
     */
    public function getNextProxy(): ?array
    {
        $available = array_values($this->getAvailableProxies());
        
        if (empty($available)) {
            return null;
        }

        $proxy = $available[$this->currentIndex % count($available)];
        $this->currentIndex++;

        return $proxy;
    }

    /**
     * Get random proxy
     */
    public function getRandomProxy(): ?array
    {
        $available = $this->getAvailableProxies();
        
        if (empty($available)) {
            return null;
        }

        return $available[array_rand($available)];
    }

    /**
     * Mark proxy as failed
     */
    public function markAsFailed(string $proxyUrl): void
    {
        if (!in_array($proxyUrl, $this->failedProxies)) {
            $this->failedProxies[] = $proxyUrl;
            Log::warning('Proxy marked as failed: ' . $proxyUrl);
        }
    }

    /**
     * Reset failed proxies
     */
    public function resetFailed(): void
    {
        $count = count($this->failedProxies);
        $this->failedProxies = [];
        Log::info('Reset ' . $count . ' failed proxies');
    }

    /**
     * Validate proxy by making a test request
     */
    public function validateProxy(array $proxy): bool
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)
                ->withOptions([
                    'proxy' => $this->buildProxyUrl($proxy)
                ])
                ->get('https://httpbin.org/ip');
            
            return $response->successful();
        } catch (\Exception $e) {
            Log::debug('Proxy validation failed: ' . $proxy['url'], ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Build full proxy URL with credentials
     */
    private function buildProxyUrl(array $proxy): string
    {
        $url = $proxy['url'];
        
        if (!empty($proxy['username']) && !empty($proxy['password'])) {
            // Extract scheme and host from URL
            $parts = parse_url($url);
            $url = $parts['scheme'] . '://' . $proxy['username'] . ':' . $proxy['password'] . '@' . $parts['host'];
            
            if (isset($parts['port'])) {
                $url .= ':' . $parts['port'];
            }
        }
        
        return $url;
    }

    /**
     * Get total proxy count
     */
    public function getTotalCount(): int
    {
        return count($this->proxies);
    }
}
