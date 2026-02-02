<?php

namespace App\Contracts;

use Illuminate\Support\Collection;

interface CsvParserInterface
{
    /**
     * Parse CSV data and return collection of products
     *
     * @param array $config Configuration from supplier.source_config
     * @return Collection Collection of arrays with keys: sku, title, price
     *
     * @throws \InvalidArgumentException If config is invalid
     * @throws \RuntimeException If parsing fails
     */
    public function parse(array $config): Collection;

    /**
     * Get the source type this parser handles
     *
     * @return string 'local', 'ftp', or 'http'
     */
    public function getSourceType(): string;
}
