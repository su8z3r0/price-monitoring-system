<?php

namespace App\Services;

use App\Contracts\CsvParserInterface;
use App\Services\Csv\LocalCsvParser;
use App\Services\Csv\FtpCsvParser;
use App\Services\Csv\HttpCsvParser;

class CsvParserFactory
{
    /**
     * Create appropriate CSV parser based on source type
     *
     * @param string $sourceType 'local', 'ftp', or 'http'
     * @return CsvParserInterface
     * @throws \InvalidArgumentException
     */
    public function make(string $sourceType): CsvParserInterface
    {
        return match ($sourceType) {
            'local' => new LocalCsvParser(),
            'ftp' => new FtpCsvParser(),
            'http' => new HttpCsvParser(),
            default => throw new \InvalidArgumentException("Unsupported source type: {$sourceType}"),
        };
    }

    /**
     * Get all available source types
     *
     * @return array
     */
    public function getAvailableTypes(): array
    {
        return ['local', 'ftp', 'http'];
    }
}
