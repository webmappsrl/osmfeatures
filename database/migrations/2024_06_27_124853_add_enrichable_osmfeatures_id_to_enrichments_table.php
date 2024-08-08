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
        if (!Schema::hasColumn('enrichments', 'enrichable_osmfeatures_id')) {
            Schema::table('enrichments', function (Blueprint $table) {
                $table->string('enrichable_osmfeatures_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrichments', function (Blueprint $table) {
            $table->dropColumn('enrichable_osmfeatures_id');
        });
    }
};
