<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ProxyRotator
{
    public function __construct(
        private readonly ProxyPool $proxyPool
    ) {
    }

    /**
     * Get next available proxy based on configured strategy
     */
    public function getNextProxy(): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $strategy = config('proxy.strategy', 'round_robin');
        
        $proxy = match($strategy) {
            'random' => $this->proxyPool->getRandomProxy(),
            default => $this->proxyPool->getNextProxy(),
        };

        if ($proxy) {
            Log::info('Selected proxy: ' . $proxy['url'] . ' (strategy: ' . $strategy . ')');
        }

        return $proxy;
    }

    /**
     * Mark a proxy as failed
     */
    public function markProxyAsFailed(string $proxyUrl): void
    {
        $this->proxyPool->markAsFailed($proxyUrl);
    }

    /**
     * Reset all failed proxies
     */
    public function resetFailedProxies(): void
    {
        $this->proxyPool->resetFailed();
    }

    /**
     * Check if proxy rotation is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) config('proxy.enabled', false);
    }

    /**
     * Build Guzzle proxy options from proxy config
     */
    public function buildGuzzleProxyOptions(?array $proxy): array
    {
        if (!$proxy) {
            return [];
        }

        $options = [
            'proxy' => $proxy['url']
        ];

        // Add authentication if credentials provided
        if (!empty($proxy['username']) && !empty($proxy['password'])) {
            $parsed = parse_url($proxy['url']);
            $options['proxy'] = sprintf(
                '%s://%s:%s@%s%s',
                $parsed['scheme'],
                $proxy['username'],
                $proxy['password'],
                $parsed['host'],
                isset($parsed['port']) ? ':' . $parsed['port'] : ''
            );
        }

        return $options;
    }
}
