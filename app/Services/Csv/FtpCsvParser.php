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
}
