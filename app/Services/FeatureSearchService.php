<?php

namespace App\Services;

use App\Models\AdminArea;
use App\Models\HikingRoute;
use App\Models\Place;
use App\Models\Pole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class FeatureSearchService
{
    private const MAX_RESULTS_PER_MODEL = 50;

    /**
     * @var array<string, array{class: class-string, table: string, modes: array<int, string>}>
     */
    private array $modelConfig = [
        'admin-areas' => [
            'class' => AdminArea::class,
            'table' => 'admin_areas',
            'modes' => ['point'],
        ],
        'hiking-routes' => [
            'class' => HikingRoute::class,
            'table' => 'hiking_routes',
            'modes' => ['radius', 'bbox'],
        ],
        'places' => [
            'class' => Place::class,
            'table' => 'places',
            'modes' => ['radius', 'bbox'],
        ],
        'poles' => [
            'class' => Pole::class,
            'table' => 'poles',
            'modes' => ['radius', 'bbox'],
        ],
    ];

    /**
     * @return string[]
     */
    public function availableModels(): array
    {
        return array_keys($this->modelConfig);
    }

    /**
     * @return string[]
     */
    public function resolveModels(?string $modelsCsv): array
    {
        if ($modelsCsv === null || trim($modelsCsv) === '') {
            return $this->availableModels();
        }

        $models = array_values(array_filter(array_map(
            static fn(string $value): string => trim($value),
            explode(',', $modelsCsv)
        )));

        if ($models === []) {
            return $this->availableModels();
        }

        $unknownModels = array_values(array_diff($models, $this->availableModels()));
        if ($unknownModels !== []) {
            throw new InvalidArgumentException('Invalid models: ' . implode(', ', $unknownModels));
        }

        return array_values(array_unique($models));
    }

    public function supportsMode(string $modelSlug, string $mode): bool
    {
        return in_array($mode, $this->modelConfig[$modelSlug]['modes'], true);
    }

    /**
     * @param string[] $models
     * @return string[]
     */
    public function modelsSupportingMode(array $models, string $mode): array
    {
        return array_values(array_filter(
            $models,
            fn(string $model): bool => $this->supportsMode($model, $mode)
        ));
    }

    /**
     * @param string[] $models
     * @return string[]
     */
    public function modelsWithExistingTables(array $models): array
    {
        return array_values(array_filter($models, function (string $model): bool {
            return Schema::hasTable($this->modelConfig[$model]['table']);
        }));
    }

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
            $query = $modelClass::query()
                ->orderBy('updated_at', 'desc')
                ->limit(self::MAX_RESULTS_PER_MODEL);

            $this->applySpatialFilter($query, $mode, $filters);

            foreach ($query->get() as $record) {
                $feature = $record->getGeojsonFeature();
                $feature['properties']['model'] = $modelSlug;
                $features[] = $feature;
            }
        }

        return $features;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applySpatialFilter(Builder $query, string $mode, array $filters): void
    {
        $geom4326 = $this->geometry4326Expression('geom');

        if ($mode === 'bbox') {
            /** @var array{minLon: float, minLat: float, maxLon: float, maxLat: float} $bbox */
            $bbox = $filters['bbox'];
            $query->whereRaw(
                "ST_Intersects(($geom4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))",
                [$bbox['minLon'], $bbox['minLat'], $bbox['maxLon'], $bbox['maxLat']]
            );
            return;
        }

        /** @var float $lon */
        $lon = $filters['lon'];
        /** @var float $lat */
        $lat = $filters['lat'];

        if ($mode === 'point') {
            $query->whereRaw(
                "ST_Covers(($geom4326), ST_SetSRID(ST_MakePoint(?, ?), 4326))",
                [$lon, $lat]
            );
            return;
        }

        /** @var float $radius */
        $radius = $filters['radius'];
        $query->whereRaw(
            "ST_DWithin((($geom4326)::geography), (ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography), ?)",
            [$lon, $lat, $radius]
        );
    }

    private function geometry4326Expression(string $column): string
    {
        $geomAsGeometry = "($column::geometry)";

        return "CASE
            WHEN ST_SRID($geomAsGeometry) = 4326 THEN $geomAsGeometry
            WHEN ST_SRID($geomAsGeometry) = 0 THEN ST_SetSRID($geomAsGeometry, 4326)
            ELSE ST_Transform($geomAsGeometry, 4326)
        END";
    }
}
