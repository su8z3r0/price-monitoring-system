<?php

namespace App\Services;

use App\Models\Competitor;
use App\Repositories\CompetitorPriceRepository;
use App\Utils\PriceParser;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Log\LoggerInterface;

class CrawlerService
{
    private const RATE_LIMIT_SECONDS = 60;
    private const CONFIG_PATH_MAX_RETRIES = 'price_intelligent/proxy/max_retries'; // Kept for constant reference, though config() is used

    // Properties mapped from Magento
    protected $logger;
    protected $priceParser;
    protected $proxyRotator;
    protected $scopeConfig; // Simulated

    public function __construct(
        LoggerInterface $logger,
        CompetitorPriceRepository $repo,
        ProxyPool $proxyPool
    ) {
        $this->logger = $logger;
        $this->repo = $repo;
        $this->proxyRotator = $proxyPool;
        // PriceParser is static in this project, so we don't inject it as an instance
    }

    /**
     * Scrape all active competitors
     * (Laravel Orchestration Wrapper)
     */
    public function scrapeAll(?\Closure $onProgress = null): array
    {
        $competitors = Competitor::where('is_active', true)->get();
        $results = [];

        foreach ($competitors as $competitor) {
            if ($onProgress) $onProgress('info', "Starting competitor: {$competitor->name}");
            try {
                $count = $this->scrapeCompetitor($competitor, $onProgress);
                $results[$competitor->name] = ['success' => true, 'count' => $count];
            } catch (\Exception $e) {
                $results[$competitor->name] = ['success' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Scrape products from a competitor website
     * (Laravel Orchestration Wrapper)
     */
    public function scrapeCompetitor(Competitor $competitor, ?\Closure $onProgress = null): int
    {
        $config = $competitor->crawler_config;
        if (is_string($config)) {
            $config = trim($config, '"');
            $config = str_replace('\n', '', $config);
            $config = stripslashes($config);
            $config = json_decode($config, true);
        }

        if (!isset($config['product_urls']) && !isset($config['base_url'])) {
            throw new \InvalidArgumentException('Config must have either "product_urls" or "base_url"');
        }

        $productUrls = $this->getProductUrls($config);
        $count = 0;

        foreach ($productUrls as $url) {
            if ($onProgress) $onProgress('scraping', $url);

            try {
                // Call the Magento-ported method
                $productData = $this->scrapeProduct($config, $url); 

                if ($productData) {
                    // Normalize data for storage
                    // Magento returns: ean, product_title, sale_price, scraped_at, product_url
                    // Laravel needs: sku (we use EAN or title hash if missing logic), normalized_sku
                    
                    // We need a SKU. Magento logic returns 'ean' as a primary key often.
                    $sku = $productData['ean'];
                    
                    // Fallback to extraction if EAN is null (handled in extraction logic)
                    if (empty($sku)) {
                        // If EAN is missing, maybe we can find a SKU selector if defined in config, 
                        // BUT user said "Simply use Magento class". Magento class ONLY extracts EAN.
                        // So we must trust EAN is found or we fail? 
                        // Let's add a small check to use SKU from selector if EAN failed, purely as a safety,
                        // or just skip if no unique ID.
                        // Re-reading Magento code: it only extracts EAN.
                        
                        // We will check if we can get SKU from selector as fallback to be safe for existing data
                        // But we will do it AFTER calling the strict method.
                        // Actually, strict Magento port means we only get EAN.
                        // If EAN is null, we can't save to our DB which requires SKU.
                        // We'll proceed with EAN as SKU.
                    }

                    if ($sku) {
                        $this->repo->create([
                            'competitor_id' => $competitor->id,
                            'sku' => $sku,
                            'normalized_sku' => SkuNormalizer::normalize($sku),
                            'product_title' => $productData['product_title'],
                            'sale_price' => $productData['sale_price'],
                            'product_url' => $productData['product_url'],
                            'scraped_at' => $productData['scraped_at'],
                        ]);
                        $count++;
                        if ($onProgress) $onProgress('generated', "{$productData['product_title']} ({$sku})");
                    } else {
                        Log::warning("Skipped product (No EAN/SKU found): $url");
                    }
                }

                $delay = $this->proxyRotator->getTotalCount() > 0 ? 2 : self::RATE_LIMIT_SECONDS;
                if ($onProgress) $onProgress('wait', (string)$delay);
                sleep($delay);

            } catch (\Exception $e) {
                Log::error('Failed to scrape product', ['url' => $url, 'error' => $e->getMessage()]);
                if ($onProgress) $onProgress('error', $e->getMessage());
                continue;
            }
        }
        return $count;
    }

    private function getProductUrls(array $config): array
    {
        if (isset($config['product_urls'])) return $config['product_urls'];
        return [$config['base_url']];
    }

    // =================================================================================================
    // PORTED MAGENTO CODE STARTS HERE
    // =================================================================================================

    public function scrapeProduct(array $config, string $url): array
    {
        // $maxRetries = (int) $this->scopeConfig->getValue(self::CONFIG_PATH_MAX_RETRIES) ?: 3;
        $maxRetries = config('proxy.max_retries', 3);
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $ch = null;
            try {
                // Get proxy if enabled
                $proxy = $this->proxyRotator->getNextProxy();
                
                $ch = curl_init();
                
                // Generic Options
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Prevent infinite loops
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                // Connection hygiene
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
                
                // Headers & User Agent
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
                
                $headers = [
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                    'Accept-Language: it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Cache-Control: max-age=0',
                    'Upgrade-Insecure-Requests: 1',
                    'Sec-Fetch-Dest: document',
                    'Sec-Fetch-Mode: navigate',
                    'Sec-Fetch-Site: none',
                    'Sec-Fetch-User: ?1',
                    'Connection: close' // Prevent lingering connections
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                
                // Enable automatic decompression (gzip, deflate, br)
                curl_setopt($ch, CURLOPT_ENCODING, ''); 
                
                // Enable request header tracking for debugging
                curl_setopt($ch, CURLINFO_HEADER_OUT, true);

                // Note: REMOVED CURLOPT_COOKIEJAR/COOKIEFILE to completely disable cookie engine.
                // This prevents "Request Header Too Long" caused by cookie accumulation on redirects.
                
                // Proxy Configuration
                if ($proxy) {
                    curl_setopt($ch, CURLOPT_PROXY, $proxy['url']);
                    
                    // Handle Protocol
                    if (isset($proxy['protocol'])) {
                        switch ($proxy['protocol']) {
                            case 'socks5':
                                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
                                break;
                            case 'socks4':
                                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
                                break;
                            case 'http':
                            default:
                                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
                                break;
                        }
                    }

                    if (!empty($proxy['username']) && !empty($proxy['password'])) {
                        curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ':' . $proxy['password']);
                    }
                    $this->logger->info('Scraping with proxy: ' . $proxy['url'] . ' (' . ($proxy['protocol'] ?? 'http') . ')');
                }
                
                // Disable SSL Verification (Keep this fix)
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $html = curl_exec($ch);
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $requestHeaders = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                
                curl_close($ch);
                $ch = null;

                if ($errno || $html === false) {
                    $this->logger->error('CURL Request Headers: ' . $requestHeaders);
                    throw new \RuntimeException('CURL Error: ' . $error);
                }

                if ($httpCode >= 400) {
                    $this->logger->error('CURL Request Headers (HTTP ' . $httpCode . '): ' . $requestHeaders);
                    throw new \RuntimeException('HTTP Error: ' . $httpCode);
                }
                
                if (empty($html)) {
                    $this->logger->error('CURL Request Headers (Empty Response): ' . $requestHeaders);
                    throw new \RuntimeException('Empty response from server');
                }
                
                $crawler = new Crawler($html);

                return [
                    'product_url' => $url,
                    'ean' => $this->extractEan($crawler, $config),
                    'product_title' => trim($this->extractTitle($crawler, $config)),
                    'sale_price' => $this->extractPrice($crawler, $config),
                    'scraped_at' => date('Y-m-d H:i:s'),
                ];
                
            } catch (\Exception $e) {
                if ($ch) {
                    curl_close($ch);
                }
                $lastException = $e;
                $attempt++;
                
                // Mark proxy as failed if used
                if ($proxy) {
                    $this->proxyRotator->markAsFailed($proxy['url']); // Adapted method name
                    $this->logger->warning('Proxy failed, attempt ' . $attempt . '/' . $maxRetries, [
                        'proxy' => $proxy['url'],
                        'error' => $e->getMessage()
                    ]);
                } else {
                    $this->logger->warning('Scraping failed (no proxy), attempt ' . $attempt . '/' . $maxRetries, [
                        'url' => $url,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Sleep before retry
                if ($attempt < $maxRetries) {
                    sleep(2);
                }
            }
        }
        
        // All retries failed
        $this->logger->error('Scraping failed after ' . $maxRetries . ' attempts', [
            'url' => $url,
            'last_error' => $lastException->getMessage()
        ]);
        
        throw $lastException;
    }

    protected function extractEan(Crawler $crawler, array $config): ?string
    {
        $method = $config['selectors']['ean']['method'] ?? 'json_ld';

        switch ($method) {
            case 'json_ld':
                return $this->extractEanFromJsonLd($crawler, $config['selectors']['ean']['field'] ?? 'gtin13');
            case 'meta':
                return $this->extractEanFromMeta($crawler);
            case 'data_attribute':
                return $this->extractEanFromDataAttribute($crawler, $config['selectors']['ean']['attribute'] ?? 'data-ean');
        }

        return null;
    }

    protected function extractEanFromJsonLd(Crawler $crawler, string $field): ?string
    {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');
            foreach ($scripts as $script) {
                // Fix: explicit textContent access for Symfony Crawler
                $content = trim($script->textContent);
                if (empty($content)) {
                    continue;
                }

                $data = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                $result = $this->findFieldRecursive($data, $field);
                if ($result) {
                    return (string)$result;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('JSON-LD extraction failed: ' . $e->getMessage());
        }

        return null;
    }

    private function findFieldRecursive(array $data, string $field): ?string
    {
        // Direct match
        if (isset($data[$field]) && (is_string($data[$field]) || is_numeric($data[$field]))) {
            return (string)$data[$field];
        }

        // Handle @graph
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $item) {
                if (is_array($item)) {
                    $result = $this->findFieldRecursive($item, $field);
                    if ($result) return $result;
                }
            }
        }

        // Handle array of items (e.g. valid JSON-LD can be specific list of objects)
        if (array_keys($data) === range(0, count($data) - 1)) {
            foreach ($data as $item) {
                if (is_array($item)) {
                    $result = $this->findFieldRecursive($item, $field);
                    if ($result) return $result;
                }
            }
        }

        return null;
    }

    protected function extractEanFromMeta(Crawler $crawler): ?string
    {
        try {
            $meta = $crawler->filter('meta[itemprop="gtin13"]')->first();
            if ($meta->count()) return $meta->attr('content');
        } catch (\Exception $e) {}

        try {
            $meta = $crawler->filter('meta[property="product:ean"]')->first();
            if ($meta->count()) return $meta->attr('content');
        } catch (\Exception $e) {}

        return null;
    }

    protected function extractEanFromDataAttribute(Crawler $crawler, string $attribute): ?string
    {
        try {
            $element = $crawler->filter("[{$attribute}]")->first();
            if ($element->count()) return $element->attr($attribute);
        } catch (\Exception $e) {}

        return null;
    }

    protected function extractTitle(Crawler $crawler, array $config): string
    {
        return $crawler->filter($config['selectors']['title'] ?? 'h1')->first()->text();
    }

    protected function extractPrice(Crawler $crawler, array $config): float
    {
        $priceText = $crawler->filter($config['selectors']['price'] ?? '.price')->first()->text();
        return PriceParser::parse($priceText); // Adapted for static method
    }
}
