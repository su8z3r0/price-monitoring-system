<?php

namespace App\Services;

use App\Repositories\BestSupplierProductRepository;
use App\Repositories\BestCompetitorPriceRepository;
use App\Repositories\PriceComparisonRepository;

class CompetitivenessAnalysisService
{
    public function __construct(
        private BestSupplierProductRepository $supplierRepo,
        private BestCompetitorPriceRepository $competitorRepo,
        private PriceComparisonRepository $comparisonRepo
    ) {}

    /**
     * Perform full competitiveness analysis
     *
     * @return int Number of comparisons created
     */
    public function analyze(): int
    {
        $this->comparisonRepo->truncate();

        $supplierProducts = $this->supplierRepo->getAll()->keyBy('sku');
        $competitorPrices = $this->competitorRepo->getAll()->keyBy('sku');

        $comparisons = [];
        $now = now();

        foreach ($supplierProducts as $sku => $supplierProduct) {
            if (!isset($competitorPrices[$sku])) {
                continue;
            }

            $competitorPrice = $competitorPrices[$sku];

            $ourPrice = $supplierProduct->price;
            $theirPrice = $competitorPrice->sale_price;

            $priceDifference = $ourPrice - $theirPrice;
            $isCompetitive = $ourPrice <= $theirPrice;
            $competitivenessPercentage = $theirPrice > 0
                ? round(($ourPrice / $theirPrice) * 100, 2)
                : null;

            $comparisons[] = [
                'sku' => $sku,
                'product_title' => $supplierProduct->title,
                'our_price' => $ourPrice,
                'competitor_price' => $theirPrice,
                'price_difference' => $priceDifference,
                'is_competitive' => $isCompetitive,
                'competitiveness_percentage' => $competitivenessPercentage,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($comparisons)) {
            $this->comparisonRepo->bulkInsert($comparisons);
        }

        return count($comparisons);
    }

    /**
     * Get analysis statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->comparisonRepo->getStatistics();
    }

    /**
     * Get top competitive products
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getTopCompetitive(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->comparisonRepo->getCompetitive()
            ->sortBy('competitiveness_percentage')
            ->take($limit);
    }

    /**
     * Get products needing price adjustment
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getProductsNeedingAdjustment(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->comparisonRepo->getNonCompetitive()
            ->sortByDesc('price_difference')
            ->take($limit);
    }
}
