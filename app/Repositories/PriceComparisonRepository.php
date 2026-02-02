<?php

namespace App\Repositories;

use App\Models\PriceComparison;
use Illuminate\Support\Collection;

class PriceComparisonRepository
{
    public function __construct(
        private PriceComparison $model
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
     * Create new comparison record
     *
     * @param array $data
     * @return PriceComparison
     */
    public function create(array $data): PriceComparison
    {
        return $this->model->create($data);
    }

    /**
     * Bulk insert comparisons
     *
     * @param array $comparisons
     * @return bool
     */
    public function bulkInsert(array $comparisons): bool
    {
        return $this->model->insert($comparisons);
    }

    /**
     * Get all comparisons
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get competitive products only
     *
     * @return Collection
     */
    public function getCompetitive(): Collection
    {
        return $this->model
            ->where('is_competitive', true)
            ->get();
    }

    /**
     * Get non-competitive products
     *
     * @return Collection
     */
    public function getNonCompetitive(): Collection
    {
        return $this->model
            ->where('is_competitive', false)
            ->get();
    }

    /**
     * Count total comparisons
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
        $all = $this->model->all();

        return [
            'total_comparisons' => $all->count(),
            'competitive_count' => $all->where('is_competitive', true)->count(),
            'non_competitive_count' => $all->where('is_competitive', false)->count(),
            'average_our_price' => $all->avg('our_price'),
            'average_competitor_price' => $all->avg('competitor_price'),
            'average_difference' => $all->avg('price_difference'),
            'competitive_percentage' => $all->count() > 0
                ? round(($all->where('is_competitive', true)->count() / $all->count()) * 100, 2)
                : 0,
        ];
    }
}
