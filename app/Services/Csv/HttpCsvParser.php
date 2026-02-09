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

        $result = $this->parseFile($tempFile, $config['columns']);

        unlink($tempFile);

        return $result;
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
     * Validate required configuration keys
     *
     * @param array $config
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateConfig(array $config): void
    {
        if (!isset($config['url'])) {
            throw new \InvalidArgumentException('Missing "url" in config');
        }

        if (!isset($config['columns'])) {
            throw new \InvalidArgumentException('Missing "columns" in config');
        }
    }

    /**
     * Download CSV file from URL
     *
     * @param array $config
     * @return string File content
     * @throws \RuntimeException
     */
    private function downloadFile(array $config): string
    {
        $headers = $config['headers'] ?? [];

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->get($config['url']);

        if (!$response->successful()) {
            throw new \RuntimeException("Failed to download file from: {$config['url']} (HTTP {$response->status()})");
        }

        return $response->body();
    }

    /**
     * Save content to temporary file
     *
     * @param string $content
     * @return string Path to temporary file
     */
    private function saveTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'http_csv_');
        file_put_contents($tempFile, $content);

        return $tempFile;
    }

    /**
     * Parse CSV file
     *
     * @param string $filePath
     * @param array $columnMap
     * @return Collection
     */
    private function parseFile(string $filePath, array $columnMap): Collection
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        return $this->normalizeData($csv->getRecords(), $columnMap);
    }
}
