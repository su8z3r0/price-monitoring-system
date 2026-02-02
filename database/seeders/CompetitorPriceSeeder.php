<?php

namespace Database\Seeders;

use App\Models\Competitor;
use App\Models\CompetitorPrice;
use Illuminate\Database\Seeder;

class CompetitorPriceSeeder extends Seeder
{
    public function run(): void
    {
        $competitors = Competitor::all();

        if ($competitors->isEmpty()) {
            $this->command->error('No competitors found! Run CompetitorSeeder first.');
            return;
        }

        $products = [
            ['sku' => 'GUIT001', 'title' => 'Fender Stratocaster'],
            ['sku' => 'GUIT002', 'title' => 'Gibson Les Paul'],
            ['sku' => 'GUIT003', 'title' => 'Ibanez RG Series'],
            ['sku' => 'BASS001', 'title' => 'Fender Precision Bass'],
            ['sku' => 'BASS002', 'title' => 'Music Man StingRay'],
            ['sku' => 'DRUM001', 'title' => 'Pearl Export Series Drum Kit'],
            ['sku' => 'DRUM002', 'title' => 'Tama Imperialstar'],
            ['sku' => 'KEYB001', 'title' => 'Yamaha P-125 Digital Piano'],
            ['sku' => 'KEYB002', 'title' => 'Roland FP-30X'],
            ['sku' => 'AMP001', 'title' => 'Marshall DSL40CR'],
            ['sku' => 'AMP002', 'title' => 'Fender Blues Junior'],
            ['sku' => 'MIC001', 'title' => 'Shure SM58'],
            ['sku' => 'MIC002', 'title' => 'Audio-Technica AT2020'],
            ['sku' => 'PERC001', 'title' => 'LP Bongo Set'],
            ['sku' => 'PERC002', 'title' => 'Meinl Cajon'],
        ];

        $count = 0;

        foreach ($competitors as $competitor) {
            foreach ($products as $product) {
                // Prezzi random ma realistici
                $basePrice = $this->getBasePrice($product['sku']);
                $variation = rand(-20, 30); // Variazione ±20-30%
                $price = round($basePrice + ($basePrice * $variation / 100), 2);

                CompetitorPrice::create([
                    'competitor_id' => $competitor->id,
                    'sku' => $product['sku'],
                    'product_title' => $product['title'],
                    'sale_price' => $price,
                    'product_url' => $competitor->website . '/product/' . strtolower($product['sku']),
                    'scraped_at' => now()->subDays(rand(0, 7)),
                ]);

                $count++;
            }
        }

        $this->command->info("Created {$count} competitor prices");
    }

    private function getBasePrice(string $sku): float
    {
        // Prezzi base realistici per categoria
        return match(true) {
            str_starts_with($sku, 'GUIT') => rand(300, 1500),
            str_starts_with($sku, 'BASS') => rand(400, 1200),
            str_starts_with($sku, 'DRUM') => rand(500, 2000),
            str_starts_with($sku, 'KEYB') => rand(400, 1000),
            str_starts_with($sku, 'AMP') => rand(200, 800),
            str_starts_with($sku, 'MIC') => rand(50, 300),
            str_starts_with($sku, 'PERC') => rand(80, 400),
            default => rand(100, 500),
        };
    }
}
