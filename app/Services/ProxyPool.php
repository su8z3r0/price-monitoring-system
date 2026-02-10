<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProxyPool
{
    private array $proxies = [];
    private array $failedProxies = [];
    private int $currentIndex = 0;

    public function __construct(
        private readonly GeoNodeProxyService $geoNodeService
    ) {
        $this->loadProxies();
    }

    /**
     * Load proxies from configuration and GeoNode
     */
    private function loadProxies(): void
    {
        $configProxies = config('proxy.list', []);
        $geoNodeProxies = $this->geoNodeService->getProxies();

        $this->proxies = array_merge($configProxies, $geoNodeProxies);

        Log::info('ProxyPool loaded ' . count($this->proxies) . ' proxies (Config: ' . count($configProxies) . ', GeoNode: ' . count($geoNodeProxies) . ')');
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
     * Build Guzzle proxy options
     */
    public function buildGuzzleProxyOptions(array $proxy): array
    {
        return [
            'proxy' => $proxy['url']
        ];
    }
}
