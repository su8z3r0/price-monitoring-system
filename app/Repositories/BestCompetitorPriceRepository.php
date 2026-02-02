<?php

namespace App\Repositories;

use App\Models\BestCompetitorPrice;
use Illuminate\Support\Collection;

class BestCompetitorPriceRepository
{
    public function __construct(
        private BestCompetitorPrice $model
    ) {}

    /**
     * Create new record
     * @param array $data
     * @return BestCompetitorPrice
     */
    public function create(array $data): BestCompetitorPrice
    {
        return $this->model->create($data);
    }

    /**
     * Get all best prices
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get best prie by sku
     * @param string $sku
     * @return BestCompetitorPrice|null
     */
    public function getBySku(string $sku): ?BestCompetitorPrice
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Update or create row
     * @param array $attributes
     * @param array $values
     * @return BestCompetitorPrice
     */
    public function updateOrCreate(array $attributes, array $values): BestCompetitorPrice
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Cunt records
     * @return int
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Truncate best_competitor_prices table
     * @return void
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }
}
