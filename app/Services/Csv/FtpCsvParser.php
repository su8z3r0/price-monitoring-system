<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class FtpCsvParser implements CsvParserInterface
{
    /**
     * Parse CSV file from FTP server
     *
     * @param array $config Configuration array with 'host', 'username', 'password', 'path', 'columns'
     * @return Collection
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function parse(array $config): Collection
    {
        $this->validateConfig($config);

        $ftpConnection = $this->connectToFtp($config);

        $tempFile = $this->downloadFile($ftpConnection, $config['path']);

        ftp_close($ftpConnection);

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
        return 'ftp';
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
        $required = ['host', 'username', 'password', 'path', 'columns'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new \InvalidArgumentException("Missing \"{$key}\" in config");
            }
        }
    }

    /**
     * Connect to FTP server
     *
     * @param array $config
     * @return resource
     * @throws \RuntimeException
     */
    private function connectToFtp(array $config)
    {
        $connection = ftp_connect($config['host']);

        if (!$connection) {
            throw new \RuntimeException("Failed to connect to FTP server: {$config['host']}");
        }

        $login = ftp_login($connection, $config['username'], $config['password']);

        if (!$login) {
            ftp_close($connection);
            throw new \RuntimeException("Failed to login to FTP server");
        }

        ftp_pasv($connection, true);

        return $connection;
    }

    /**
     * Download file from FTP to temporary location
     *
     * @param resource $connection
     * @param string $remotePath
     * @return string Path to temporary file
     * @throws \RuntimeException
     */
    private function downloadFile($connection, string $remotePath): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ftp_csv_');

        if (!ftp_get($connection, $tempFile, $remotePath, FTP_BINARY)) {
            unlink($tempFile);
            throw new \RuntimeException("Failed to download file: {$remotePath}");
        }

        return $tempFile;
    }

    /**
     * Parse downloaded CSV file
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
