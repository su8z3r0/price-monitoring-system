<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BestSupplierProduct extends Model
{
    protected $fillable = [
        'sku',
        'normalized_sku',
        'title',
        'price',
        'winner_supplier_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function winnerSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'winner_supplier_id');
    }
}
