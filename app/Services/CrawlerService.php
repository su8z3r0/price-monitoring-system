<?php

namespace App\Services;

use App\Models\Competitor;
use App\Repositories\CompetitorPriceRepository;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class CrawlerService
{
    private const RATE_LIMIT_SECONDS = 60;

    public function __construct(
        private CompetitorPriceRepository $repo
    ) {}

    /**
     * Scrape products from a competitor website
     *
     * @param Competitor $competitor
     * @return int Number of products scraped
     * @throws \RuntimeException
     */
    public function scrapeCompetitor(Competitor $competitor): int
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

        if (!isset($config['selectors'])) {
            throw new \InvalidArgumentException('Missing "selectors" in config');
        }

        $productUrls = $this->getProductUrls($config);

        $count = 0;
        foreach ($productUrls as $url) {
            try {
                $productData = $this->scrapeProduct($url, $config['selectors']);

                if ($productData) {
                    $this->repo->create([
                        'competitor_id' => $competitor->id,
                        'sku' => $productData['sku'],
                        'product_title' => $productData['title'],
                        'sale_price' => $productData['price'],
                        'product_url' => $url,
                        'scraped_at' => now(),
                    ]);

                    $count++;
                }

                sleep(self::RATE_LIMIT_SECONDS);

            } catch (\Exception $e) {
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
     * Scrape single product page
     *
     * @param string $url
     * @param array $selectors
     * @return array|null
     * @throws \RuntimeException
     */
    private function scrapeProduct(string $url, array $selectors): ?array
    {
        $response = Http::timeout(30)
            ->get($url);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to fetch URL: {$url}");
        }

        $html = $response->body();
        $crawler = new Crawler($html);

        try {
            $sku = $this->extractText($crawler, $selectors['sku'] ?? null);
            $title = $this->extractText($crawler, $selectors['title'] ?? null);
            $priceText = $this->extractText($crawler, $selectors['price'] ?? null);

            if (!$sku || !$title || !$priceText) {
                return null;
            }

            return [
                'sku' => $sku,
                'title' => $title,
                'price' => $this->parsePrice($priceText),
            ];

        } catch (\Exception $e) {
            return null;
        }
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
}
