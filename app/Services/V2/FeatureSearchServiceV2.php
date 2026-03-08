<?php

namespace App\Services\V2;

use App\Services\FeatureSearchService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Versione ottimizzata di FeatureSearchService.
 *
 * Ottimizzazioni rispetto a V1:
 * - ST_AsGeoJSON calcolato inline nella SELECT → zero query aggiuntive per la geometria
 * - Eager loading degli enrichment → nessun lazy load N+1
 * - Da potenziali 204 query (v1) a 4 query totali (una per modello)
 */
class FeatureSearchServiceV2 extends FeatureSearchService
{
    /**
     * Relazioni da eager-load per modello.
     */
    private array $eagerLoad = [
        'admin-areas' => ['enrichment'],
        'hiking-routes' => ['demEnrichment', 'adminAreasEnrichment'],
        'places' => ['enrichment'],
        'poles' => ['enrichment'],
    ];

    /**
     * @param string[] $models
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function search(array $models, string $mode, array $filters): array
    {
        $features = [];

        foreach ($models as $modelSlug) {
            $modelClass = $this->modelConfig[$modelSlug]['class'];

            /** @var Builder $query */
            $query = $modelClass::selectRaw('*, ST_AsGeoJSON(ST_Transform(geom, 4326)) as geojson_geom')
                ->with($this->eagerLoad[$modelSlug] ?? [])
                ->orderBy('updated_at', 'desc')
                ->limit(self::MAX_RESULTS_PER_MODEL);

            $this->applySpatialFilter($query, $mode, $filters);

            foreach ($query->get() as $record) {
                $feature = $record->getGeojsonFeatureV2($record->geojson_geom);
                $feature['properties']['model'] = $modelSlug;
                $features[] = $feature;
            }
        }

        return $features;
    }
}
