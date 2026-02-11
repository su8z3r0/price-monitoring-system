<?php

namespace App\Console\Commands;

use App\Services\SupplierImportService;
use Illuminate\Console\Command;

class SupplierMatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cyper:supplier:match
                            {--supplier= : Import only specific supplier by ID}
                            {--skip-best : Skip best products calculation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import supplier products from CSV sources and calculate best prices';

    /**
     * Execute the console command.
     */
    public function handle(SupplierImportService $service): int
    {
        $this->info('Starting supplier import...');
        $this->newLine();

        if ($supplierId = $this->option('supplier')) {
            return $this->importSingleSupplier($service, $supplierId);
        }

        return $this->importAllSuppliers($service);
    }

    /**
     * Import single supplier
     *
     * @param SupplierImportService $service
     * @param int $supplierId
     * @return int
     */
    private function importSingleSupplier(SupplierImportService $service, int $supplierId): int
    {
        $supplier = \App\Models\Supplier::find($supplierId);

        if (!$supplier) {
            $this->error("Supplier with ID {$supplierId} not found");
            return self::FAILURE;
        }

        $this->info("Importing: {$supplier->name}");

        try {
            $count = $service->importSupplier($supplier);
            $this->info("✓ Imported {$count} products from {$supplier->name}");
        } catch (\Exception $e) {
            $this->error("✗ Failed to import {$supplier->name}: {$e->getMessage()}");
            return self::FAILURE;
        }

        if (!$this->option('skip-best')) {
            $this->newLine();
            $this->calculateBestProducts($service);
        }

        return self::SUCCESS;
    }

    /**
     * Import all active suppliers
     *
     * @param SupplierImportService $service
     * @return int
     */
    private function importAllSuppliers(SupplierImportService $service): int
    {
        $results = $service->importAllSuppliers();

        $this->displayImportResults($results);

        if (!$this->option('skip-best')) {
            $this->newLine();
            $this->calculateBestProducts($service);
        }

        $this->newLine();
        $this->displayStatistics($service);

        return self::SUCCESS;
    }

    /**
     * Calculate and display best products
     *
     * @param SupplierImportService $service
     * @return void
     */
    private function calculateBestProducts(SupplierImportService $service): void
    {
        $this->info('Calculating best supplier products...');

        $count = $service->updateBestSupplierProducts();

        $this->info("✓ Updated {$count} best products");
    }

    /**
     * Display import results table
     *
     * @param array $results
     * @return void
     */
    private function displayImportResults(array $results): void
    {
        $tableData = [];

        foreach ($results as $supplierName => $result) {
            $tableData[] = [
                'supplier' => $supplierName,
                'status' => $result['success'] ? '✓ Success' : '✗ Failed',
                'products' => $result['success'] ? $result['count'] : '-',
                'error' => $result['success'] ? '' : $result['error'],
            ];
        }

        $this->table(
            ['Supplier', 'Status', 'Products', 'Error'],
            $tableData
        );
    }

    /**
     * Display statistics
     *
     * @param SupplierImportService $service
     * @return void
     */
    private function displayStatistics(SupplierImportService $service): void
    {
        $stats = $service->getStatistics();

        $this->info('📊 Statistics:');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Supplier Products', number_format($stats['total_supplier_products'])],
                ['Total Best Products', number_format($stats['total_best_products'])],
                ['Average Best Price', '€' . number_format($stats['best_products_stats']['average_price'], 2)],
                ['Min Price', '€' . number_format($stats['best_products_stats']['min_price'], 2)],
                ['Max Price', '€' . number_format($stats['best_products_stats']['max_price'], 2)],
            ]
        );
    }
}
