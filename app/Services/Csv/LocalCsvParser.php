<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use Illuminate\Support\Collection;
use League\Csv\Reader;

class LocalCsvParser implements CsvParserInterface
{
    /**
     * Parse CSV file from local storage
     *
     * @param array $config Configuration array with 'path' and 'columns'
     * @return Collection
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function parse(array $config): Collection
    {
        if (!isset($config['path'])) {
            throw new \InvalidArgumentException('Missing "path" in config');
        }

        if (!isset($config['columns'])) {
            throw new \InvalidArgumentException('Missing "columns" in config');
        }

        $filePath = storage_path('app' . $config['path']);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        return $this->normalizeData($csv->getRecords(), $config['columns']);
    }

    /**
     * Get source type identifier
     *
     * @return string
     */
    public function getSourceType(): string
    {
        return 'local';
    }

    /**
     * Normalize CSV records to standard format
     *
     * @param iterable $records
     * @param array $columnMap
     * @return Collection
     */
    private function normalizeData(iterable $records, array $columnMap): Collection
    {
        $normalized = [];

        foreach ($records as $record) {
            $normalized[] = [
                'sku' => $record[$columnMap['sku']] ?? null,
                'title' => $record[$columnMap['title']] ?? null,
                'price' => $this->parsePrice($record[$columnMap['price']] ?? '0'),
            ];
        }

        return collect($normalized)->filter(function ($item) {
            return !empty($item['sku']) && $item['price'] > 0;
        });
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
