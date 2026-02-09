<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use App\Utils\PriceParser;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Collection;

abstract class AbstractParser implements CsvParserInterface
{

    /**
     * Parse CSV file from HTTP/HTTPS URL
     *
     * @param array $config
     * @return Collection
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    abstract public function parse(array $config): Collection;

    /**
     * Get source type identifier
     *
     * @return string
     */
    abstract public function getSourceType(): string;

    /**
     * Normalize CSV records to standard format
     *
     * @param iterable $records
     * @param array $columnMap
     * @return Collection
     */
    protected function normalizeData(iterable $records, array $columnMap): Collection
    {
        $normalized = [];

        foreach ($records as $record) {

            $sku = $record[$columnMap['sku']] ?? null;
            $title = $record[$columnMap['title']] ?? null;
            $price = $this->parsePrice($record[$columnMap['price']] ?? '0');

            $normalizedSku = SkuNormalizer::normalize($sku);

            $item =  [
                'sku' => $sku,
                'normalized_sku' => $normalizedSku,
                'title' => $title,
                'price' => $price,
            ];

            if (!empty($item['normalized_sku']) && $item['price'] > 0) {
                $normalized[] = $item;
            }
        }

        return collect($normalized);
    }

    /**
     * Parse price string to float
     *
     * @param string $priceText
     * @return float
     */
    private function parsePrice(string $priceText): float
    {
        return PriceParser::parse($priceText);
    }
}
