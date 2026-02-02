<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competitor extends Model
{
    protected $fillable = [
        'name',
        'website',
        'crawler_config',
        'is_active'
    ];

    protected $casts = [
        'crawler_config' => 'array',
        'is_active' => 'boolean'
    ];

    public function competitorPrices(): HasMany
    {
        return $this->hasMany(CompetitorPrice::class);
    }
}
