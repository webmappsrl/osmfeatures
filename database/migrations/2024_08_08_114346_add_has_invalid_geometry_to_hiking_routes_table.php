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
        //if hiking_routes table exists add has_invalid_geometry
        if (Schema::hasTable('hiking_routes')) {
            Schema::table('hiking_routes', function (Blueprint $table) {
                $table->boolean('has_invalid_geometry')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hiking_routes')) {
            Schema::table('hiking_routes', function (Blueprint $table) {
                $table->dropColumn('has_invalid_geometry');
            });
        }
    }
};
