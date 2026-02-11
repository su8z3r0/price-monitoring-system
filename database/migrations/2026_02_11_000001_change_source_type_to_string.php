<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Change enum to string
            $table->string('source_type')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to enum (with original values)
        // Note: Using DB::statement because ->change() back to enum might be tricky depending on DB driver
        Schema::table('suppliers', function (Blueprint $table) {
             $table->enum('source_type', ['local', 'ftp', 'http'])->change();
        });
    }
};
