<?php

namespace App\Console\Commands;

use App\Services\CompetitivenessAnalysisService;
use Illuminate\Console\Command;

class AnalysisCompetitivenessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analysis:competitiveness
                            {--top=10 : Number of top products to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze price competitiveness by comparing our prices with competitors';

    /**
     * Execute the console command.
     */
    public function handle(CompetitivenessAnalysisService $service): int
    {
        $this->info('Starting competitiveness analysis...');
        $this->newLine();

        $count = $service->analyze();

        if ($count === 0) {
            $this->warn('No products to compare. Make sure you have run:');
            $this->line('  - php artisan competitor:find-best');
            $this->line('  - php artisan supplier:match');
            return self::FAILURE;
        }

        $this->info("✓ Analyzed {$count} products");
        $this->newLine();

        $this->displayStatistics($service);
        $this->newLine();

        $topLimit = (int) $this->option('top');
        $this->displayTopCompetitive($service, $topLimit);
        $this->newLine();

        $this->displayNeedingAdjustment($service, $topLimit);

        return self::SUCCESS;
    }

    /**
     * Display overall statistics
     *
     * @param CompetitivenessAnalysisService $service
     * @return void
     */
    private function displayStatistics(CompetitivenessAnalysisService $service): void
    {
        $stats = $service->getStatistics();

        $this->info('📊 Overall Statistics:');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Products Analyzed', number_format($stats['total_comparisons'])],
                ['Competitive Products', number_format($stats['competitive_count']) . ' (' . $stats['competitive_percentage'] . '%)'],
                ['Non-Competitive Products', number_format($stats['non_competitive_count'])],
                ['Average Our Price', '€' . number_format($stats['average_our_price'], 2)],
                ['Average Competitor Price', '€' . number_format($stats['average_competitor_price'], 2)],
                ['Average Price Difference', '€' . number_format($stats['average_difference'], 2)],
            ]
        );
    }

    /**
     * Display top competitive products
     *
     * @param CompetitivenessAnalysisService $service
     * @param int $limit
     * @return void
     */
    private function displayTopCompetitive(CompetitivenessAnalysisService $service, int $limit): void
    {
        $this->info("🏆 Top {$limit} Most Competitive Products:");
        $this->newLine();

        $products = $service->getTopCompetitive($limit);

        if ($products->isEmpty()) {
            $this->warn('No competitive products found');
            return;
        }

        $tableData = $products->map(fn ($product) => [
            'sku' => $product->sku,
            'product' => substr($product->product_title, 0, 40),
            'our_price' => '€' . number_format($product->our_price, 2),
            'competitor' => '€' . number_format($product->competitor_price, 2),
            'rate' => $product->competitiveness_percentage . '%',
        ])->toArray();

        $this->table(
            ['SKU', 'Product', 'Our Price', 'Competitor', 'Rate'],
            $tableData
        );
    }

    /**
     * Display products needing price adjustment
     *
     * @param CompetitivenessAnalysisService $service
     * @param int $limit
     * @return void
     */
    private function displayNeedingAdjustment(CompetitivenessAnalysisService $service, int $limit): void
    {
        $this->warn("⚠️  Top {$limit} Products Needing Price Adjustment:");
        $this->newLine();

        $products = $service->getProductsNeedingAdjustment($limit);

        if ($products->isEmpty()) {
            $this->info('All products are competitive! 🎉');
            return;
        }

        $tableData = $products->map(fn ($product) => [
            'sku' => $product->sku,
            'product' => substr($product->product_title, 0, 40),
            'our_price' => '€' . number_format($product->our_price, 2),
            'competitor' => '€' . number_format($product->competitor_price, 2),
            'difference' => '€' . number_format($product->price_difference, 2),
        ])->toArray();

        $this->table(
            ['SKU', 'Product', 'Our Price', 'Competitor', 'Difference'],
            $tableData
        );
    }
}
