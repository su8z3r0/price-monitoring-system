<?php

namespace App\Repositories;

use App\Models\SupplierProduct;
use Illuminate\Support\Collection;

class SupplierProductRepository
{
    public function __construct(
        private SupplierProduct $model
    ) {}

    /**
     * Get all products grouped by SKU
     *
     * @return Collection
     */
    public function getAllGroupedBySku(): Collection
    {
        return $this->model
            ->with('supplier')
            ->get()
            ->groupBy('sku');
    }

    /**
     * Get products by supplier ID
     *
     * @param int $supplierId
     * @return Collection
     */
    public function getBySupplier(int $supplierId): Collection
    {
        return $this->model
            ->where('supplier_id', $supplierId)
            ->get();
    }

    /**
     * Get products by SKU
     *
     * @param string $sku
     * @return Collection
     */
    public function getBySku(string $sku): Collection
    {
        return $this->model
            ->where('sku', $sku)
            ->with('supplier')
            ->get();
    }

    /**
     * Create new product record
     *
     * @param array $data
     * @return SupplierProduct
     */
    public function create(array $data): SupplierProduct
    {
        return $this->model->create($data);
    }

    /**
     * Bulk insert products
     *
     * @param array $products
     * @return bool
     */
    public function bulkInsert(array $products): bool
    {
        return $this->model->insert($products);
    }

    /**
     * Delete all products for a supplier
     *
     * @param int $supplierId
     * @return int Number of deleted records
     */
    public function deleteBySupplier(int $supplierId): int
    {
        return $this->model
            ->where('supplier_id', $supplierId)
            ->delete();
    }

    /**
     * Truncate table
     *
     * @return void
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }

    /**
     * Count total products
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }
}
