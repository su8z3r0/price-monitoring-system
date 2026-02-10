<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // competitor_prices
        if (!Schema::hasColumn('competitor_prices', 'ean')) {
            Schema::table('competitor_prices', function (Blueprint $table) {
                $table->string('ean')->nullable()->after('sku')->index();
            });
        }

        // supplier_products
        if (!Schema::hasColumn('supplier_products', 'ean')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->string('ean')->nullable()->after('sku')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('competitor_prices', 'ean')) {
            Schema::table('competitor_prices', function (Blueprint $table) {
                $table->dropColumn('ean');
            });
        }

        if (Schema::hasColumn('supplier_products', 'ean')) {
            Schema::table('supplier_products', function (Blueprint $table) {
                $table->dropColumn('ean');
            });
        }
    }
};
