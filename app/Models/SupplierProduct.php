<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierProduct extends Model
{
    protected $fillable = [
        'supplier_id',
        'sku',
        'title',
        'price',
        'imported_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'imported_at' => 'datetime'
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
