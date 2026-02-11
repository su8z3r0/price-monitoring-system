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

        $filePath = storage_path('app' . $config['path']);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
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
