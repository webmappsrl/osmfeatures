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
        if (Schema::hasTable('hiking_routes')) {
            Schema::table('hiking_routes', function (Blueprint $table) {
                $table->jsonb('admin_areas')->nullable();
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
                $table->dropColumn('admin_areas');
            });
        }
    }
};
