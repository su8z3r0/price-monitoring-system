<?php

namespace App\Console\Commands;

use App\Services\PriceComparisonService;
use Illuminate\Console\Command;

class CompetitorFindBestCommand extends Command
{
    protected $signature = 'competitor:find-best';
    protected $description = 'Find best competitor prices by SKU';

    public function handle(PriceComparisonService $service): int
    {
        $this->info('Finding best competitor prices...');

        $count = $service->updateBestCompetitorPrices();

        $this->info("✓ Updated {$count} best prices");

        $stats = $service->getStatistics();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products', $stats['total_products']],
                ['Average Price', '€' . number_format($stats['average_price'], 2)],
                ['Min Price', '€' . number_format($stats['min_price'], 2)],
                ['Max Price', '€' . number_format($stats['max_price'], 2)],
            ]
        );

        return self::SUCCESS;
    }
}
