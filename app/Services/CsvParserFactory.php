<?php

namespace App\Services;

use App\Contracts\CsvParserInterface;
use App\Services\Csv\LocalCsvParser;
use App\Services\Csv\FtpCsvParser;
use App\Services\Csv\HttpCsvParser;
use Illuminate\Support\Facades\Config;

class CsvParserFactory
{
    /**
     * Create appropriate CSV parser based on source type
     *
     * @param string $sourceType
     * @return CsvParserInterface
     * @throws \InvalidArgumentException
     */
    public function make(string $sourceType): CsvParserInterface
    {
        $parserClass = $this->getParserClass($sourceType);

        if (!$parserClass) {
            $availableTypes = $this->getAvailableTypes();
            throw new \InvalidArgumentException(
                "Unsupported source type: {$sourceType}. Available types: " . implode(', ', $availableTypes)
            );
        }

        if (!class_exists($parserClass)) {
            throw new \RuntimeException("Parser class not found: {$parserClass}");
        }

        $parser = new $parserClass();

        if (!$parser instanceof CsvParserInterface) {
            throw new \RuntimeException(
                "Parser class {$parserClass} must implement CsvParserInterface"
            );
        }

        return $parser;
    }

    /**
     * Get parser class for given source type
     *
     * @param string $sourceType
     * @return string|null
     */
    private function getParserClass(string $sourceType): ?string
    {
        return Config::get("csv-parsers.types.{$sourceType}");
    }

    /**
     * Get all available source types
     *
     * @return array
     */
    public function getAvailableTypes(): array
    {
        return array_keys(Config::get('csv-parsers.types', []));
    }

    /**
     * Check if a source type is supported
     *
     * @param string $sourceType
     * @return bool
     */
    public function supports(string $sourceType): bool
    {
        return $this->getParserClass($sourceType) !== null;
    }

    /**
     * Get default source type
     *
     * @return string
     */
    public function getDefaultType(): string
    {
        return Config::get('csv-parsers.default', 'local');
    }
}
