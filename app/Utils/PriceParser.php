<?php

namespace App\Utils;

class PriceParser
{
    /**
     * Parse price from text with currency symbols
     *
     * Handles various formats:
     * - "€1.234,56" → 1234.56
     * - "$1,234.56" → 1234.56
     * - "£51.77" → 51.77
     * - "1234.56 EUR" → 1234.56
     * - "USD 1,234.56" → 1234.56
     * - "1.234,56 €" → 1234.56
     * - "Price: €1.234,56" → 1234.56
     * - "¥1,234" → 1234.00
     * - "₹1,23,456.78" → 123456.78 (Indian format)
     *
     * @param string|null $priceText
     * @return float
     */
    public static function parse(?string $priceText): float
    {
        if (empty($priceText)) {
            return 0.0;
        }

        $cleaned = self::removeCurrencySymbols($priceText);
        $cleaned = self::removeCommonWords($cleaned);
        $cleaned = self::cleanNonNumeric($cleaned);

        if (empty($cleaned)) {
            return 0.0;
        }

        return self::normalizeDecimalSeparator($cleaned);
    }

    /**
     * Remove currency symbols and codes
     *
     * @param string $text
     * @return string
     */
    private static function removeCurrencySymbols(string $text): string
    {
        // Currency symbols
        $symbols = [
            '€', '$', '£', '¥', '₹', '₽', '₩', '₪', '₱', '₦', '₨', '฿', '₫', '₡', '₵',
            'CHF', 'R$', 'zł', 'Kč', 'kr', 'kn', 'lei', 'лв', 'ден', 'lek',
        ];

        // Currency codes (ISO 4217)
        $codes = [
            'EUR', 'USD', 'GBP', 'JPY', 'CNY', 'INR', 'RUB', 'KRW', 'BRL', 'CAD', 'AUD',
            'CHF', 'SEK', 'NOK', 'DKK', 'PLN', 'CZK', 'HUF', 'RON', 'BGN', 'HRK', 'TRY',
            'MXN', 'ZAR', 'THB', 'IDR', 'MYR', 'PHP', 'SGD', 'NZD', 'AED', 'SAR', 'QAR',
        ];

        // Currency names
        $names = [
            'euro', 'euros', 'dollar', 'dollars', 'pound', 'pounds', 'yen', 'rupee', 'rupees',
            'ruble', 'rubles', 'franc', 'francs', 'krona', 'kronor', 'krone', 'kroner',
        ];

        $text = str_replace($symbols, '', $text);

        foreach ($codes as $code) {
            $text = str_ireplace($code, '', $text);
        }

        foreach ($names as $name) {
            $text = str_ireplace($name, '', $text);
        }

        return $text;
    }

    /**
     * Remove common price-related words
     *
     * @param string $text
     * @return string
     */
    private static function removeCommonWords(string $text): string
    {
        $words = [
            'Price:', 'price:', 'PRICE:',
            'From', 'from', 'FROM',
            'Starting at', 'starting at', 'STARTING AT',
            'Only', 'only', 'ONLY',
            'Was:', 'was:', 'WAS:',
            'Now:', 'now:', 'NOW:',
            'Sale:', 'sale:', 'SALE:',
        ];

        foreach ($words as $word) {
            $text = str_replace($word, '', $text);
        }

        return $text;
    }

    /**
     * Remove all non-numeric characters except comma, dot, and minus
     *
     * @param string $text
     * @return string
     */
    private static function cleanNonNumeric(string $text): string
    {
        $cleaned = preg_replace('/[^0-9.,\-]/', '', $text);
        return trim($cleaned);
    }

    /**
     * Normalize decimal separator to dot
     *
     * Detects if comma or dot is the decimal separator
     *
     * @param string $text
     * @return float
     */
    private static function normalizeDecimalSeparator(string $text): float
    {
        $lastCommaPos = strrpos($text, ',');
        $lastDotPos = strrpos($text, '.');

        // Both comma and dot present
        if ($lastCommaPos !== false && $lastDotPos !== false) {
            if ($lastCommaPos > $lastDotPos) {
                // European format: 1.234,56
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                // US/UK format: 1,234.56
                $text = str_replace(',', '', $text);
            }
        }
        // Only comma present
        elseif ($lastCommaPos !== false) {
            $digitsAfterComma = strlen($text) - $lastCommaPos - 1;

            // If 2 or 3 digits after comma, it's likely a decimal separator
            if ($digitsAfterComma == 2 || ($digitsAfterComma == 3 && substr_count($text, ',') == 1)) {
                // European decimal: 1234,56 or 1.234,56
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                // Thousand separator: 1,234 or 12,345
                $text = str_replace(',', '', $text);
            }
        }
        // Only dot present or no separator - assume US format

        return (float) $text;
    }
}
