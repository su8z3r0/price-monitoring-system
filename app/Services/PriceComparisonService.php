<?php

namespace App\Services;

use App\Repositories\CompetitorPriceRepository;
use App\Repositories\BestCompetitorPriceRepository;
use Illuminate\Support\Collection;

class PriceComparisonService
{
    public function __construct(
        private CompetitorPriceRepository $competitorPriceRepo,
        private BestCompetitorPriceRepository $bestPriceRepo
    ) {}

    /**
     * Find the best competitor prices by sku and update the table
     *
     * @return int Numero di best prices aggiornati
     */
    public function updateBestCompetitorPrices(): int
    {
        $this->bestPriceRepo->truncate();

        $grouped = $this->competitorPriceRepo->getAllGroupedByNormalizedSku();

        $count = 0;
        foreach ($grouped as $normalizedSku => $prices) {
            $bestPrice = $this->findBestPrice($prices);

            if ($bestPrice) {

                $sku = $bestPrice['sku'];
                $productTitle = $bestPrice['product_title'];
                $salePrice = $bestPrice['sale_price'];
                $winnerCompetitor = $bestPrice['winner_competitor'];

                $this->bestPriceRepo->create([
                    'sku' => $sku,
                    'normalized_sku' => $normalizedSku,
                    'product_title' => $productTitle,
                    'sale_price' => $salePrice,
                    'winner_competitor' => $winnerCompetitor,
                ]);

                $count++;
            }
        }

        return $count;
    }

    /**
     * Find the lowest price fomr a ollection of prices
     * @param Collection $prices
     * @return array|null
     */
    private function findBestPrice(Collection $prices): ?array
    {
        // Ordina per sale_price crescente e prendi il primo
        $best = $prices->sortBy('sale_price')->first();

        if (!$best) {
            return null;
        }

        return [
            'product_title' => $best->product_title,
            'sale_price' => $best->sale_price,
            'winner_competitor' => $best->competitor->name,
        ];
    }

    /**
     * Get best prices stats
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->bestPriceRepo->count();
        $allPrices = $this->bestPriceRepo->getAll();

        return [
            'total_products' => $total,
            'average_price' => $allPrices->avg('sale_price'),
            'min_price' => $allPrices->min('sale_price'),
            'max_price' => $allPrices->max('sale_price'),
        ];
    }
}
