<?php

namespace App\Contracts;

interface ProxyProviderInterface
{
    /**
     * Get cached proxies or fetch if not available
     *
     * @return array
     */
    public function getProxies(): array;

    /**
     * Force update proxies from source and validate
     *
     * @return array
     */
    public function updateProxies(): array;

    /**
     * Remove a specific proxy from the cache
     *
     * @param string $proxyUrl
     * @return void
     */
    public function removeProxy(string $proxyUrl): void;
}
