<?php

namespace App\Repositories;

use App\Models\BestSupplierProduct;
use Illuminate\Support\Collection;

class BestSupplierProductRepository
{
    public function __construct(
        private BestSupplierProduct $model
    ) {}

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
     * Create new best product record
     *
     * @param array $data
     * @return BestSupplierProduct
     */
    public function create(array $data): BestSupplierProduct
    {
        return $this->model->create($data);
    }

    /**
     * Get all best products
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model
            ->with('winnerSupplier')
            ->get();
    }

    /**
     * Get best product by SKU
     *
     * @param string $sku
     * @return BestSupplierProduct|null
     */
    public function getBySku(string $sku): ?BestSupplierProduct
    {
        return $this->model
            ->where('sku', $sku)
            ->first();
    }

    /**
     * Update or create best product
     *
     * @param array $attributes
     * @param array $values
     * @return BestSupplierProduct
     */
    public function updateOrCreate(array $attributes, array $values): BestSupplierProduct
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Bulk insert best products
     *
     * @param array $products
     * @return bool
     */
    public function bulkInsert(array $products): bool
    {
        return $this->model->insert($products);
    }

    /**
     * Count total best products
     *
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Get statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $allPrices = $this->model->all();

        return [
            'total_products' => $allPrices->count(),
            'average_price' => $allPrices->avg('price'),
            'min_price' => $allPrices->min('price'),
            'max_price' => $allPrices->max('price'),
        ];
    }
}
