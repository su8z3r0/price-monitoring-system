<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Collection;
use League\Csv\Reader;

class LocalCsvParser extends AbstractParser
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

        $originalPath = $config['path'];
        $cleanPath = ltrim($originalPath, '/\\' . DIRECTORY_SEPARATOR);

        // Remove 'storage/' prefix if present to avoid duplication
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        // 1. Try storage/app/ (Standard Laravel Storage)
        $filePath = storage_path('app/' . $cleanPath);

        // 2. Try storage/ (Root Storage - legacy/manual placement)
        if (!file_exists($filePath)) {
            $candidate = storage_path($cleanPath);
            if (file_exists($candidate)) {
                $filePath = $candidate;
            }
        }

        // 3. Try as absolute path (if user knows what they are doing)
        if (!file_exists($filePath)) {
            if (file_exists($originalPath)) {
                $filePath = $originalPath;
            }
        }

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found. Checked: \n1. storage/app/{$cleanPath}\n2. storage/{$cleanPath}\n3. {$originalPath}");
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $this->configureReader($csv, $config);
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
}
