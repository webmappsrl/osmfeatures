<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dem_enrichments', function (Blueprint $table) {
            $table->boolean('enrichable_has_invalid_geometry')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dem_enrichments', function (Blueprint $table) {
            $table->dropColumn('enrichable_has_invalid_geometry');
        });
    }
};
