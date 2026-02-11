<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use League\Csv\Reader;

class HttpCsvParser extends AbstractParser
{
    /**
     * Parse CSV file from HTTP/HTTPS URL
     *
     * @param array $config Configuration array with 'url', 'columns', optional 'headers'
     * @return Collection
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function parse(array $config): Collection
    {
        $this->validateConfig($config);

        $content = $this->downloadFile($config);

        $tempFile = $this->saveTempFile($content);

        $result = $this->parseFile($tempFile, $config);

        unlink($tempFile);

        return $result;
    }

    // ... (keeping intervening methods unchanged) ...

    /**
     * Parse CSV file
     *
     * @param string $filePath
     * @param array $config
     * @return Collection
     */
    private function parseFile(string $filePath, array $config): Collection
    {
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
        return 'http';
    }

    /**
     * Validate configuration
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    protected function validateConfig(array $config): void
    {
        $required = ['url', 'columns'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new \InvalidArgumentException("Missing required config field: {$field}");
            }
        }
    }
}
