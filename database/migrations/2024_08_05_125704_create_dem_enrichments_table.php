<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dem_enrichments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->morphs('dem-enrichable');
            $table->jsonb('data')->nullable();
            $table->string('enrichable_osmfeatures_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dem_enrichments');
    }
};
