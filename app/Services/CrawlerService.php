<?php

namespace App\Services;

use App\Models\Competitor;
use App\Repositories\CompetitorPriceRepository;
use App\Utils\PriceParser;
use App\Utils\SkuGenerator;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerService
{
    private const RATE_LIMIT_SECONDS = 60;

    public function __construct(
        private CompetitorPriceRepository $repo,
        private ProxyPool $proxyPool
    ) {}

    /**
     * Scrape products from a competitor website
     *
     * @param Competitor $competitor
     * @return int Number of products scraped
     * @throws \RuntimeException
     */
    public function scrapeCompetitor(Competitor $competitor, ?\Closure $onProgress = null): int
    {
        $config = $competitor->crawler_config;

        if (is_string($config)) {
            $config = trim($config, '"'); // Remove external quotes
            $config = str_replace('\n', '', $config); // Remove \n
            $config = stripslashes($config); // Remove backslashes
            $config = json_decode($config, true);
        }

        if (!isset($config['product_urls']) && !isset($config['base_url'])) {
            throw new \InvalidArgumentException('Config must have either "product_urls" or "base_url"');
        }

        if (!isset($config['selectors'])) {
            throw new \InvalidArgumentException('Missing "selectors" in config');
        }

        $productUrls = $this->getProductUrls($config);

        $count = 0;
        foreach ($productUrls as $url) {
            if ($onProgress) {
                $onProgress('scraping', $url);
            }

            try {
                $productData = $this->scrapeProduct($url, $config['selectors'], $onProgress);

                if ($productData) {
                    $this->repo->create([
                        'competitor_id' => $competitor->id,
                        'sku' => $productData['sku'],
                        'ean' => $productData['ean'] ?? null,
                        'product_title' => $productData['title'],
                        'sale_price' => $productData['price'],
                        'product_url' => $url,
                        'scraped_at' => now(),
                    ]);

                    $count++;

                    if ($onProgress) {
                        $onProgress('generated', "{$productData['title']} ({$productData['sku']})");
                    }
                }

                $delay = $this->proxyPool->hasProxies() ? 2 : self::RATE_LIMIT_SECONDS;
                
                if ($onProgress) {
                    $onProgress('wait', (string)$delay);
                }

                Log::info("Sleeping for {$delay} seconds...");
                sleep($delay);

            } catch (\Exception $e) {
                Log::error('Failed to scrape product', ['url' => $url, 'error' => $e->getMessage()]);
                if ($onProgress) {
                    $onProgress('error', $e->getMessage());
                }
                continue;
            }
        }

        return $count;
    }

    /**
     * Scrape all active competitors
     *
     * @return array Results per competitor
     */
    public function scrapeAll(): array
    {
        $competitors = Competitor::where('is_active', true)->get();

        $results = [];

        foreach ($competitors as $competitor) {
            try {
                $count = $this->scrapeCompetitor($competitor);
                $results[$competitor->name] = [
                    'success' => true,
                    'count' => $count,
                ];
            } catch (\Exception $e) {
                $results[$competitor->name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get list of product URLs to scrape
     *
     * @param array $config
     * @return array
     */
    private function getProductUrls(array $config): array
    {
        $baseUrl = $config['base_url'];

        if (isset($config['product_urls'])) {
            return $config['product_urls'];
        }

        return [$baseUrl];
    }

    /**
     * Scrape single product page with proxy rotation and EAN extraction
     *
     * @param string $url
     * @param array $selectors
     * @return array|null
     * @throws \RuntimeException
     */
    private function scrapeProduct(string $url, array $selectors, ?\Closure $onProgress = null): ?array
    {
        $maxRetries = config('proxy.max_retries', 3);
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                // Get proxy if enabled
                $proxy = $this->proxyPool->getNextProxy();
                
                // Build HTTP client with proxy and headers
                $http = Http::timeout(config('proxy.timeout', 30))
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                        'Accept-Language' => 'it-IT,it;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Cache-Control' => 'max-age=0',
                        'Upgrade-Insecure-Requests' => '1',
                        'Sec-Fetch-Dest' => 'document',
                        'Sec-Fetch-Mode' => 'navigate',
                        'Sec-Fetch-Site' => 'none',
                        'Sec-Fetch-User' => '?1',
                        'Connection' => 'close',
                    ]);
                
                if ($proxy) {
                    $proxyOptions = $this->proxyPool->buildGuzzleProxyOptions($proxy);
                    $http = $http->withOptions($proxyOptions);
                    Log::info('Scraping with proxy: ' . $proxy['url'], ['url' => $url]);
                    if ($onProgress) {
                        $onProgress('debug', "   -> Proxy: {$proxy['url']}");
                    }
                }
                
                $response = $http->get($url);

                if (!$response->successful()) {
                    $body = substr($response->body(), 0, 500);
                    Log::error("HTTP Error {$response->status()} for URL: {$url}", ['body' => $body]); 
                    throw new \RuntimeException("HTTP {$response->status()}: {$body}");
                }

                $html = $response->body();
                
                if (empty($html)) {
                    throw new \RuntimeException("Empty response from server");
                }
                
                $crawler = new Crawler($html);

                $sku = $this->extractText($crawler, $selectors['sku'] ?? null);
                $title = $this->extractText($crawler, $selectors['title'] ?? null);
                $ean = $this->extractEan($crawler);
                $priceText = $this->extractText($crawler, $selectors['price'] ?? null);

                if (!$sku || !$title || !$priceText) {
                    return null;
                }

                return [
                    'sku' => $sku,
                    'ean' => $ean,
                    'title' => $title,
                    'price' => $this->parsePrice($priceText),
                ];

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                // Mark proxy as failed if used
                if ($proxy) {
                    $this->proxyPool->markAsFailed($proxy['url']);
                    Log::warning('Proxy failed, attempt ' . $attempt . '/' . $maxRetries, [
                        'proxy' => $proxy['url'],
                        'error' => $e->getMessage()
                    ]);
                } else {
                    Log::warning('Scraping failed (no proxy), attempt ' . $attempt . '/' . $maxRetries, [
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
        Log::error('Scraping failed after ' . $maxRetries . ' attempts', [
            'url' => $url,
            'last_error' => $lastException?->getMessage()
        ]);
        
        return null; // Return null instead of throwing to allow continuing with other products
    }

    /**
     * Extract text from DOM using CSS selector
     *
     * @param Crawler $crawler
     * @param string|null $selector
     * @return string|null
     */
    private function extractText(Crawler $crawler, ?string $selector): ?string
    {
        if (!$selector) {
            return null;
        }

        try {
            return $crawler->filter($selector)->first()->text();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse price string to float
     *
     * @param string $priceText
     * @return float
     */
    private function parsePrice(string $priceText): float
    {
        $clean = preg_replace('/[^0-9,.]/', '', $priceText);

        if (empty($clean)) {
            return 0.0;
        }

        if (substr_count($clean, ',') === 1 && substr_count($clean, '.') >= 1) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (substr_count($clean, ',') >= 1) {
            $clean = str_replace(',', '', $clean);
        } elseif (substr_count($clean, ',') === 1) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }

    /**
     * Extract EAN using multiple strategies
     */
    private function extractEan(Crawler $crawler): ?string
    {
        // 1. JSON-LD
        $ean = $this->extractEanFromJsonLd($crawler);
        if ($ean) return $ean;

        // 2. Meta Tags
        $ean = $this->extractEanFromMeta($crawler);
        if ($ean) return $ean;

        // 3. Data Attributes (generic)
        $ean = $this->extractEanFromDataAttribute($crawler, 'data-ean');
        if ($ean) return $ean;

        return null;
    }

    private function extractEanFromJsonLd(Crawler $crawler): ?string
    {
        try {
            $scripts = $crawler->filter('script[type="application/ld+json"]');
            
            foreach ($scripts as $script) {
                $json = json_decode($script->nodeValue, true);
                if (!$json) continue;

                $ean = $this->findFieldRecursive($json, ['gtin13', 'gtin', 'ean']);
                if ($ean) return $this->cleanupEan($ean);
            }
        } catch (\Exception $e) {}

        return null;
    }

    private function extractEanFromMeta(Crawler $crawler): ?string
    {
        $metas = [
            'meta[itemprop="gtin13"]',
            'meta[property="product:ean"]',
            'meta[name="ean"]'
        ];

        foreach ($metas as $selector) {
            try {
                $element = $crawler->filter($selector)->first();
                if ($element->count()) {
                    return $this->cleanupEan($element->attr('content'));
                }
            } catch (\Exception $e) {}
        }

        return null;
    }

    private function extractEanFromDataAttribute(Crawler $crawler, string $attribute): ?string
    {
        try {
            $element = $crawler->filter("[{$attribute}]")->first();
            if ($element->count()) return $this->cleanupEan($element->attr($attribute));
        } catch (\Exception $e) {}

        return null;
    }

    private function findFieldRecursive(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->findFieldRecursive($value, $keys);
                if ($found) return $found;
            }
        }

        return null;
    }

    private function cleanupEan(string $ean): string
    {
        return trim(preg_replace('/[^0-9]/', '', $ean));
    }
}
