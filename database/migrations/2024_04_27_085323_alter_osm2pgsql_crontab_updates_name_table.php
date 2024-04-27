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
        Schema::rename('osm2pgsql_crontab_updates', 'osm2pgsql_last_updates');

        Schema::table('osm2pgsql_last_updates', function (Blueprint $table) {
            $table->dropColumn('from_lua');
            $table->dropColumn('from_pbf');
            $table->dropColumn('success');
            $table->dropColumn('log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('osm2pgsql_last_updates', 'osm2pgsql_crontab_updates');

        Schema::table('osm2pgsql_crontab_updates', function (Blueprint $table) {
            $table->boolean('from_lua')->default(false);
            $table->boolean('from_pbf')->default(false);
            $table->boolean('success')->default(false);
            $table->string('log')->nullable();
        });
    }
};
