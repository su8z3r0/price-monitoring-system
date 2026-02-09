<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitorPrice extends Model
{
    protected $fillable = [
        'competitor_id',
        'sku',
        'normalized_sku',
        'product_title',
        'sale_price',
        'product_url',
        'scraped_at'
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'scraped_at' => 'datetime'
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(Competitor::class);
    }
}
