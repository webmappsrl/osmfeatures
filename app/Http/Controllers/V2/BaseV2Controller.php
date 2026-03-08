<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class BaseV2Controller extends Controller
{
    protected const PER_PAGE = 1000;
    protected const CACHE_TTL_LIST = 300;   // 5 minuti
    protected const CACHE_TTL_SHOW = 3600;  // 1 ora

    /**
     * Espressione SQL che restituisce geom in SRID 4326,
     * gestendo i casi in cui il SRID della colonna sia diverso.
     * Equivalente a quello usato in FeatureSearchService.
     */
    protected function geom4326Expression(string $column = 'geom'): string
    {
        return "CASE
            WHEN ST_SRID({$column}::geometry) = 4326 THEN {$column}::geometry
            WHEN ST_SRID({$column}::geometry) = 0    THEN ST_SetSRID({$column}::geometry, 4326)
            ELSE ST_Transform({$column}::geometry, 4326)
        END";
    }

    /**
     * Normalizza il parametro bbox accettando sia "minLon,minLat,maxLon,maxLat" sia "[minLon,minLat,maxLon,maxLat]".
     */
    protected function normalizeBboxParam(string $bboxParam): string
    {
        $s = trim($bboxParam);
        if (str_starts_with($s, '[')) {
            $s = substr($s, 1);
        }
        if (str_ends_with($s, ']')) {
            $s = substr($s, 0, -1);
        }
        $parts = array_map('trim', explode(',', $s));

        return implode(',', $parts);
    }

    /**
     * Applica il filtro bbox alla query usando l'indice spaziale.
     */
    protected function applyBboxFilter(Builder $query, string $bboxParam): void
    {
        $bbox = array_map('floatval', explode(',', $this->normalizeBboxParam($bboxParam)));
        $geom4326 = $this->geom4326Expression();
        $query->whereRaw(
            "ST_Intersects(($geom4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))",
            [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]
        );
    }

    /**
     * Valida la bbox e ritorna un errore se non valida.
     * Accetta sia "minLon,minLat,maxLon,maxLat" sia "[minLon,minLat,maxLon,maxLat]".
     */
    protected function validateBbox(string $bboxParam): ?JsonResponse
    {
        $parts = explode(',', $this->normalizeBboxParam($bboxParam));
        if (count($parts) !== 4) {
            return response()->json(['message' => 'Bounding box non valido: richiesti esattamente 4 valori'], 400);
        }
        if (array_filter($parts, fn($v) => !is_numeric(trim($v))) !== []) {
            return response()->json(['message' => 'Bounding box non valido: i valori devono essere numerici'], 400);
        }

        return null;
    }

    /**
     * Trasforma la collection paginata nel formato osmfeatures_id => updated_at.
     */
    protected function transformListCollection($paginator): mixed
    {
        $paginator->getCollection()->transform(function ($row) {
            $row->updated_at = Carbon::parse($row->updated_at)->toIso8601String();
            $row->id = $row->osm_type . $row->osm_id;
            unset($row->osm_type, $row->osm_id);

            return $row;
        });

        return $paginator;
    }

    /**
     * Aggiunge header HTTP caching alla risposta show().
     */
    protected function withShowCacheHeaders(JsonResponse $response, string $etag, string $updatedAt): JsonResponse
    {
        return $response->withHeaders([
            'ETag' => '"' . $etag . '"',
            'Last-Modified' => Carbon::parse($updatedAt)->toRfc7231String(),
            'Cache-Control' => 'public, max-age=' . self::CACHE_TTL_SHOW,
        ]);
    }

    /**
     * Aggiunge header HTTP caching alla risposta list().
     */
    protected function withListCacheHeaders(JsonResponse $response): JsonResponse
    {
        return $response->withHeaders([
            'Cache-Control' => 'public, max-age=' . self::CACHE_TTL_LIST,
        ]);
    }

    /**
     * Chiave cache per i parametri di una lista.
     */
    protected function listCacheKey(string $model, Request $request): string
    {
        return "v2:{$model}:list:" . md5(json_encode($request->query()));
    }
}
