<?php

namespace App\Services\Csv;

use App\Contracts\CsvParserInterface;
use App\Utils\SkuNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class FtpCsvParser extends AbstractParser
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

        $result = $this->parseFile($tempFile, $config);

        unlink($tempFile);

        return $result;
    }

    // ... (keeping intervening methods unchanged) ...

    /**
     * Parse downloaded CSV file
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
        return 'ftp';
    }

    /**
     * Validate configuration
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    protected function validateConfig(array $config): void
    {
        $required = ['host', 'username', 'password', 'path', 'columns'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                throw new \InvalidArgumentException("Missing required config field: {$field}");
            }
        }
    }

    /**
     * Connect to FTP server
     *
     * @param array $config
     * @return mixed FTP connection resource
     * @throws \RuntimeException
     */
    private function connectToFtp(array $config)
    {
        $host = $config['host'];
        $port = $config['port'] ?? 21;
        $timeout = 30;

        $conn = ftp_connect($host, $port, $timeout);

        if (!$conn) {
            throw new \RuntimeException("Could not connect to FTP server: {$host}");
        }

        if (!@ftp_login($conn, $config['username'], $config['password'])) {
            throw new \RuntimeException("Could not login to FTP server: {$host}");
        }

        ftp_pasv($conn, true);

        return $conn;
    }

    /**
     * Download file from FTP
     *
     * @param mixed $conn FTP connection resource
     * @param string $remotePath
     * @return string Local temp file path
     * @throws \RuntimeException
     */
    private function downloadFile($conn, string $remotePath): string
    {
        $tempFile = @tempnam(sys_get_temp_dir(), 'csv_ftp_');
        $handle = fopen($tempFile, 'w');

        if (!ftp_fget($conn, $handle, $remotePath, FTP_ASCII)) {
            fclose($handle);
            throw new \RuntimeException("Could not download file from FTP: {$remotePath}");
        }

        fclose($handle);
        return $tempFile;
    }
}
