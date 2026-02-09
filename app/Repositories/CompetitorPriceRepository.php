<?php

namespace App\Repositories;

use App\Models\CompetitorPrice;
use Illuminate\Support\Collection;

class CompetitorPriceRepository
{
    public function __construct(
        private CompetitorPrice $model
    ) {}

    /**
     * Get all prices grouped by sku
     * @return Collection
     */
    public function getAllGroupedBySku(): Collection
    {
        return $this->model
            ->with('competitor')
            ->orderBy('sku')
            ->get()
            ->groupBy('sku');
    }

    /**
     * Get all prices grouped by normalized_sku
     * @return Collection
     */
    public function getAllGroupedByNormalizedSku(): Collection
    {
        return $this->model
            ->with('competitor')
            ->orderBy('normalized_sku')
            ->get()
            ->groupBy('normalized_sku');
    }

    /**
     * Get specific competitor prices
     * @param int $competitorId
     * @return Collection
     */
    public function getByCompetitor(int $competitorId): Collection
    {
        return $this->model
            ->where('competitor_id', $competitorId)
            ->get();
    }

    /**
     * Get prices by sku
     * @param string $sku
     * @return Collection
     */
    public function getBySku(string $sku): Collection
    {
        return $this->model
            ->where('sku', $sku)
            ->with('competitor')
            ->get();
    }

    /**
     * Create a new record
     * @param array $data
     * @return CompetitorPrice
     */
    public function create(array $data): CompetitorPrice
    {
        return $this->model->create($data);
    }

    /**
     * Truncate competitors_prices table
     * @return void
     */
    public function truncate(): void
    {
        $this->model->truncate();
    }
}
