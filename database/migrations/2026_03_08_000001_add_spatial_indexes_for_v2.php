<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * CONCURRENTLY non può girare dentro una transazione.
     */
    /** @var bool */
    public $withinTransaction = false;

    public function up(): void
    {
        $tables = ['places', 'poles', 'hiking_routes', 'admin_areas', 'pois'];

        foreach ($tables as $table) {
            // Indice spaziale GIST sulla colonna geom (per dati già in 4326)
            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_{$table}_geom
                ON {$table} USING GIST (geom)
            ");

            // Indice funzionale GIST su ST_Transform(geom, 4326) per dati in SRID diverso
            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_{$table}_geom_4326
                ON {$table} USING GIST (ST_Transform(geom, 4326))
            ");

            // Indice composito (osm_type, osm_id) — usato da getOsmfeaturesByOsmfeaturesID()
            DB::statement("
                CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_{$table}_osm_type_osm_id
                ON {$table} (osm_type, osm_id)
            ");
        }
    }

    public function down(): void
    {
        $tables = ['places', 'poles', 'hiking_routes', 'admin_areas', 'pois'];

        foreach ($tables as $table) {
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS idx_{$table}_geom");
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS idx_{$table}_geom_4326");
            DB::statement("DROP INDEX CONCURRENTLY IF EXISTS idx_{$table}_osm_type_osm_id");
        }
    }
};
