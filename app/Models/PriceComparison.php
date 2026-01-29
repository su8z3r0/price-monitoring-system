<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceComparison extends Model
{
    protected $fillable = [
        'sku',
        'product_title',
        'our_price',
        'competitor_price',
        'price_difference',
        'is_competitive',
        'competitiveness_percentage'
    ];

    protected $casts = [
        'our_price' => 'decimal:2',
        'competitor_price' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'is_competitive' => 'boolean',
        'competitiveness_percentage' => 'decimal:2'
    ];
}
