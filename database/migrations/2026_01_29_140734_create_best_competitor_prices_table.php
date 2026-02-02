<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('best_competitor_prices', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('product_title');
            $table->decimal('sale_price', 10, 2);
            $table->string('winner_competitor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('best_competitor_prices');
    }
};
