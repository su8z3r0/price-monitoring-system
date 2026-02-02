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
        Schema::create('price_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('product_title');
            $table->decimal('our_price', 10, 2);
            $table->decimal('competitor_price', 10, 2);
            $table->decimal('price_difference', 10, 2);
            $table->boolean('is_competitive');
            $table->decimal('competitiveness_percentage', 5, 2)->nullable();
            $table->timestamps();
            $table->index('sku');
            $table->index('is_competitive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_comparisons');
    }
};
