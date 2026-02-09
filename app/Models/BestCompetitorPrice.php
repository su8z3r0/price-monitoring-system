<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BestCompetitorPrice extends Model
{
    protected $fillable = [
        'sku',
        'normalized_sku',
        'product_title',
        'sale_price',
        'winner_competitor'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2'
    ];
}
