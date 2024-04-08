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
        Schema::create('osm2pgsql_crontab_updates', function (Blueprint $table) {
            $table->id();
            $table->dateTime('imported_at');
            $table->string('from_lua');
            $table->string('from_pbf');
            $table->boolean('success')->default(true);
            $table->string('log')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('last_osm2pgsql_import');
    }
};
