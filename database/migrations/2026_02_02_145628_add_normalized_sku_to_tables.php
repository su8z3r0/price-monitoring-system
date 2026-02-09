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
        // competitor_prices
        if (!Schema::hasColumn('competitor_prices', 'normalized_sku')) {
            Schema::table('competitor_prices', function (Blueprint $table) {
                $table->string('normalized_sku')->after('sku')->index();
            });
        }

        // best_competitor_prices
        if (!Schema::hasColumn('best_competitor_prices', 'normalized_sku')) {
            Schema::table('best_competitor_prices', function (Blueprint $table) {
                $table->string('normalized_sku')->after('sku')->index();
            });
        }

        // supplier_products
        if (!Schema::hasColumn('supplier_products', 'normalized_sku')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->string('normalized_sku')->after('sku')->index();
            });
        }

        // best_supplier_products
        if (!Schema::hasColumn('best_supplier_products', 'normalized_sku')) {
            Schema::table('best_supplier_products', function (Blueprint $table) {
                $table->string('normalized_sku')->after('sku')->index();
            });
        }

        // price_comparisons
        if (!Schema::hasColumn('price_comparisons', 'normalized_sku')) {
            Schema::table('price_comparisons', function (Blueprint $table) {
                $table->string('normalized_sku')->after('sku')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // competitor_prices
        if (Schema::hasColumn('competitor_prices', 'normalized_sku')) {
            Schema::table('competitor_prices', function (Blueprint $table) {
                $table->dropIndex(['normalized_sku']);
                $table->dropColumn('normalized_sku');
            });
        }

        // best_competitor_prices
        if (Schema::hasColumn('best_competitor_prices', 'normalized_sku')) {
            Schema::table('best_competitor_prices', function (Blueprint $table) {
                $table->dropIndex(['normalized_sku']);
                $table->dropColumn('normalized_sku');
            });
        }

        // supplier_products
        if (Schema::hasColumn('supplier_products', 'normalized_sku')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->dropIndex(['normalized_sku']);
                $table->dropColumn('normalized_sku');
            });
        }

        // best_supplier_products
        if (Schema::hasColumn('best_supplier_products', 'normalized_sku')) {
            Schema::table('best_supplier_products', function (Blueprint $table) {
                $table->dropIndex(['normalized_sku']);
                $table->dropColumn('normalized_sku');
            });
        }

        // price_comparisons
        if (Schema::hasColumn('price_comparisons', 'normalized_sku')) {
            Schema::table('price_comparisons', function (Blueprint $table) {
                $table->dropIndex(['normalized_sku']);
                $table->dropColumn('normalized_sku');
            });
        }
    }
};
