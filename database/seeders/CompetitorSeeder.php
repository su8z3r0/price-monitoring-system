<?php

namespace Database\Seeders;

use App\Models\Competitor;
use Illuminate\Database\Seeder;

class CompetitorSeeder extends Seeder
{
    public function run(): void
    {
        $competitors = [
            [
                'name' => 'MusicStore.it',
                'website' => 'https://www.musicstore.it',
                'crawler_config' => json_encode([
                    'base_url' => 'https://www.musicstore.it/products',
                    'selectors' => [
                        'price' => '.product-price',
                        'title' => '.product-title',
                    ]
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'CentroChitarre.com',
                'website' => 'https://www.centrochitarre.com',
                'crawler_config' => json_encode([
                    'base_url' => 'https://www.centrochitarre.com/shop',
                    'selectors' => [
                        'price' => '.price',
                        'title' => 'h1.title',
                    ]
                ]),
                'is_active' => true,
            ],
            [
                'name' => 'StrumentiMusicali.net',
                'website' => 'https://www.strumentimusicali.net',
                'crawler_config' => json_encode([
                    'base_url' => 'https://www.strumentimusicali.net/catalog',
                    'selectors' => [
                        'price' => 'span.sale-price',
                        'title' => '.product-name',
                    ]
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($competitors as $competitor) {
            Competitor::create($competitor);
        }

        $this->command->info('Created ' . count($competitors) . ' competitors');
    }
}
