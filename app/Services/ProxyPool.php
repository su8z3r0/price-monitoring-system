<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ProxyPool
{
    private array $proxies = [];
    private array $failedProxies = [];
    private int $currentIndex = 0;

    public function __construct(
        private readonly GeoNodeProxyService $geoNodeProvider,
        private readonly ProxiflyProxyService $proxiflyProvider
    ) {
        $this->loadProxies();
    }

    /**
     * Load proxies from enabled providers based on configuration
     */
    private function loadProxies(): void
    {
        // Check if proxy system is globally disabled
        if (!config('proxy.enabled', true)) {
            Log::info('Proxy system is disabled via configuration');
            return;
        }

        $providers = config('proxy.providers', []);

        // Load from GeoNode if enabled
        if (($providers['geonode']['enabled'] ?? false)) {
            try {
                $geoNodeProxies = $this->geoNodeProvider->getProxies();
                foreach ($geoNodeProxies as $proxy) {
                    if (!isset($proxy['protocol'])) {
                        $proxy['protocol'] = 'http';
                    }
                    $this->proxies[] = $proxy;
                }
                Log::info('Loaded ' . count($geoNodeProxies) . ' proxies from GeoNode');
            } catch (\Exception $e) {
                Log::error('Failed to load proxies from GeoNode: ' . $e->getMessage());
            }
        }

        // Load from Proxifly if enabled
        if (($providers['proxifly']['enabled'] ?? false)) {
            try {
                $proxiflyProxies = $this->proxiflyProvider->getProxies();
                foreach ($proxiflyProxies as $proxy) {
                    if (!isset($proxy['protocol'])) {
                        $proxy['protocol'] = 'http';
                    }
                    $this->proxies[] = $proxy;
                }
                Log::info('Loaded ' . count($proxiflyProxies) . ' proxies from Proxifly');
            } catch (\Exception $e) {
                Log::error('Failed to load proxies from Proxifly: ' . $e->getMessage());
            }
        }

        // Load from manual list if enabled
        if (($providers['manual']['enabled'] ?? false)) {
            $manualProxies = $providers['manual']['list'] ?? [];
            
            foreach ($manualProxies as $proxyUrl) {
                $proxyUrl = trim($proxyUrl);
                if (empty($proxyUrl) || str_starts_with($proxyUrl, '#')) {
                    continue;
                }

                $this->proxies[] = [
                    'url' => $proxyUrl,
                    'protocol' => 'http', // Default for manual config
                ];
            }
            
            if (count($manualProxies) > 0) {
                Log::info('Loaded ' . count($manualProxies) . ' manual proxies from configuration');
            }
        }

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
        $available = $this->getAvailableProxies();
        
        if (empty($available)) {
            return null;
        }

        // Reset to indexed array
        $available = array_values($available);
        
        // Round-robin selection
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
            
            // Remove from provider cache
            try {
                $this->proxyProvider->removeProxy($proxyUrl);
            } catch (\Exception $e) {
                Log::warning('Could not remove proxy from provider: ' . $e->getMessage());
            }
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
     * Get total proxy count
     */
    public function getTotalCount(): int
    {
        return count($this->proxies);
    }
}
