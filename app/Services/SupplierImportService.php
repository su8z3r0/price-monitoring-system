<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\SupplierProductRepository;
use App\Repositories\BestSupplierProductRepository;
use Illuminate\Support\Collection;

class SupplierImportService
{
    public function __construct(
        private CsvParserFactory $parserFactory,
        private SupplierProductRepository $supplierProductRepo,
        private BestSupplierProductRepository $bestProductRepo
    ) {}

    /**
     * Import products from a specific supplier
     *
     * @param Supplier $supplier
     * @return int Number of products imported
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function importSupplier(Supplier $supplier, ?\Closure $onProgress = null): int
    {
        if ($onProgress) $onProgress('info', 'Parsing CSV config...');
        $parser = $this->parserFactory->make($supplier->source_type);

        $config = $supplier->source_config;

        if (is_string($config)) {
            $config = trim($config, '"'); // Rimuovi apici esterni
            $config = str_replace('\n', '', $config); // Rimuovi \n
            $config = stripslashes($config); // Rimuovi backslashes
            $config = json_decode($config, true);
        }

        if ($onProgress) $onProgress('info', 'Reading and parsing file...');
        $products = $parser->parse($config);
        $count = $products->count();
        if ($onProgress) $onProgress('info', "Parsed {$count} items.");

        $this->supplierProductRepo->deleteBySupplier($supplier->id);

        if ($onProgress) $onProgress('info', 'Saving products to database...');
        return $this->saveProducts($supplier->id, $products);
    }

    /**
     * Import products from all active suppliers
     *
     * @return array Statistics per supplier
     */
    public function importAllSuppliers(): array
    {
        $suppliers = Supplier::where('is_active', true)->get();

        $results = [];

        foreach ($suppliers as $supplier) {
            try {
                $count = $this->importSupplier($supplier);
                $results[$supplier->name] = [
                    'success' => true,
                    'count' => $count,
                ];
            } catch (\Exception $e) {
                $results[$supplier->name] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Update best supplier products table
     *
     * @return int Number of best products updated
     */
    public function updateBestSupplierProducts(): int
    {
        $this->bestProductRepo->truncate();

        $grouped = $this->supplierProductRepo->getAllGroupedByNormalizedSku();

        $count = 0;
        foreach ($grouped as $normalizedSku => $products) {
            $bestProduct = $products->sortBy('price')->first();

            if ($bestProduct) {
                $this->bestProductRepo->create([
                    'sku' => $bestProduct->sku,
                    'normalized_sku' => $normalizedSku,
                    'title' => $bestProduct->title,
                    'price' => $bestProduct->price,
                    'winner_supplier_id' => $bestProduct->supplier_id,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Import all suppliers and update best products (full workflow)
     *
     * @return array
     */
    public function fullImportWorkflow(): array
    {
        $importResults = $this->importAllSuppliers();

        $bestCount = $this->updateBestSupplierProducts();

        $stats = $this->bestProductRepo->getStatistics();

        return [
            'import_results' => $importResults,
            'best_products_count' => $bestCount,
            'statistics' => $stats,
        ];
    }

    /**
     * Save products to database
     *
     * @param int $supplierId
     * @param Collection $products
     * @return int
     */
    private function saveProducts(int $supplierId, Collection $products): int
    {
        $data = [];
        $now = now();

        foreach ($products as $product) {
            $data[] = [
                'supplier_id' => $supplierId,
                'sku' => $product['sku'],
                'normalized_sku' => $product['normalized_sku'],
                'title' => $product['title'],
                'price' => $product['price'],
                'imported_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            $this->supplierProductRepo->bulkInsert($data);
        }

        return count($data);
    }

    /**
     * Find best product from collection (lowest price)
     *
     * @param Collection $products
     * @return array|null
     */
    private function findBestProduct(Collection $products): ?array
    {
        $best = $products->sortBy('price')->first();

        if (!$best) {
            return null;
        }

        return [
            'title' => $best->title,
            'price' => $best->price,
            'supplier_id' => $best->supplier_id,
        ];
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_supplier_products' => $this->supplierProductRepo->count(),
            'total_best_products' => $this->bestProductRepo->count(),
            'best_products_stats' => $this->bestProductRepo->getStatistics(),
        ];
    }
}
