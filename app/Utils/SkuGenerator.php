<?php

namespace App\Utils;

class SkuGenerator
{
    /**
     * Smart SKU generation with fallback chain
     *
     * Priority order:
     * 1. Use scraped SKU if valid
     * 2. Extract from URL (most reliable)
     * 3. Generate from brand + title
     * 4. Generate from title only
     * 5. Hash of URL (last resort)
     *
     * @param string|null $sku Scraped SKU (if available)
     * @param string $url Product URL
     * @param string $title Product title
     * @return string
     */
    public static function smart(?string $sku, string $url, string $title): string
    {
        // 1. If SKU exists and looks valid, use it
        if (!empty($sku) && strlen($sku) >= 3) {
            return $sku;
        }

        // 2. Try to extract from URL (most reliable)
        $urlSku = self::fromUrl($url);
        if (strlen($urlSku) >= 3 && !str_starts_with($urlSku, 'gen-')) {
            return $urlSku;
        }

        // 3. Generate from brand + model (if detectable)
        $brand = self::extractBrand($title);
        if ($brand) {
            $model = self::fromTitle($title, 2);
            return "{$brand}-{$model}";
        }

        // 4. Use title-based SKU
        $titleSku = self::fromTitle($title, 3);
        if (strlen($titleSku) >= 5) {
            return $titleSku;
        }

        // 5. Fallback: hash of URL (ensures uniqueness)
        return self::generateHash($url);
    }

    /**
     * Generate SKU from product URL
     *
     * Examples:
     * - "https://site.com/product/guitar-fender-123" → "guitar-fender-123"
     * - "https://site.com/p/12345" → "12345"
     * - "https://site.com/catalogue/a-light-in-the-attic_1000/index.html" → "1000"
     *
     * @param string $url
     * @return string
     */
    public static function fromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!$path) {
            return self::generateHash($url);
        }

        // Remove common extensions
        $path = preg_replace('/\.(html|htm|php|asp|aspx)$/i', '', $path);

        // Extract last segment
        $segments = array_filter(explode('/', trim($path, '/')));
        $lastSegment = end($segments);

        // Try to extract ID from segment
        // Pattern: "product-name_123" or "product-name-123"
        if (preg_match('/[_\-](\d+)$/', $lastSegment, $matches)) {
            return $matches[1];
        }

        // Pattern: "123-product-name"
        if (preg_match('/^(\d+)[_\-]/', $lastSegment, $matches)) {
            return $matches[1];
        }

        // Use entire last segment
        return $lastSegment;
    }

    /**
     * Generate SKU from product title
     *
     * Examples:
     * - "Fender Stratocaster American Professional II" → "fender-stratocaster-american"
     * - "Gibson Les Paul Standard 60s" → "gibson-les-paul-standard"
     *
     * @param string $title
     * @param int $maxWords Maximum words to include (default: 3)
     * @return string
     */
    public static function fromTitle(string $title, int $maxWords = 3): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);

        $words = preg_split('/\s+/', trim($slug));
        $words = array_slice($words, 0, $maxWords);

        return implode('-', $words);
    }

    /**
     * Extract brand from title
     *
     * @param string $title
     * @return string|null
     */
    public static function extractBrand(string $title): ?string
    {
        $brands = [
            'Fender', 'Gibson', 'Ibanez', 'PRS', 'ESP', 'Jackson', 'Schecter',
            'Epiphone', 'Squier', 'Yamaha', 'Taylor', 'Martin',
            'Marshall', 'Orange', 'Mesa Boogie', 'VOX', 'Blackstar',
            'Pearl', 'Tama', 'DW', 'Ludwig', 'Mapex',
            'Korg', 'Roland', 'Nord', 'Casio',
        ];

        $titleLower = strtolower($title);

        foreach ($brands as $brand) {
            if (stripos($titleLower, strtolower($brand)) !== false) {
                return strtolower($brand);
            }
        }

        return null;
    }

    /**
     * Generate hash-based SKU (fallback)
     *
     * @param string $input
     * @return string
     */
    public static function generateHash(string $input): string
    {
        return 'gen-' . substr(md5($input), 0, 10);
    }
}
