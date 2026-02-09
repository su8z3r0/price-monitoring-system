<?php

namespace App\Utils;

class SkuNormalizer
{
    /**
     * Normalize SKU for consistent matching
     *
     * Removes: spaces, hyphens, underscores, special characters
     * Converts: to lowercase
     *
     * Examples:
     * - "YAM-P45" -> "yamp45"
     * - "YAMP45" -> "yamp45"
     * - "Yam P-45" -> "yamp45"
     * - "YAM_P45" -> "yamp45"
     *
     * @param string|null $sku
     * @return string
     */
    public static function normalize(?string $sku): string
    {
        if (empty($sku)) {
            return '';
        }

        return strtolower(preg_replace('/[^a-z0-9]/i', '', $sku));
    }
}
