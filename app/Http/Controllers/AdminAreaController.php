<?php

namespace App\Http\Controllers;

use App\Models\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AdminAreaController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/list",
     *     operationId="listAdminAreas",
     *     tags={"AdminAreas"},
     *     summary="List all Admin Areas",
     *     description="Returns a list of Admin Areas with their details. Optionally, provide an 'updated_at' parameter to filter areas updated after the specified date.",
     *     @OA\Parameter(
     *         name="updated_after",
     *         in="query",
     *         description="Filter by the updated timestamp. Only areas updated after this date will be returned. The date should be in ISO 8601 format.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time",
     *             example="2021-03-10T02:00:00Z"
     *         )
     *     ),
     * @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number to retrieve. Each page contains 100 results.",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             example="1"
     *         )
     *     ),
     * @OA\Parameter(
     *         name="bbox",
     *         in="query",
     *         description="Bounding box to filter areas within, specified as 'lonmin,latmin,lonmax,latmax'.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             example="12.496366,41.902783,12.507366,41.912783"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/AdminAreaItem")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $query = DB::table('admin_areas');
        $perPage = 100;

        $updated_at = $request->query('updated_at');
        $bbox = $request->query('bbox');
        $adminLevel = $request->query('admin_level');
        $score = $request->query('score');
        $isTest = $request->query('testdata');

        if ($updated_at) {
            $query->where('updated_at', '>', $updated_at);
        }

        if ($bbox) {
            $bbox = explode(',', $bbox);
            if (count($bbox) !== 4) {
                return response()->json(['message' => 'Bounding box not valid'], 400);
            }
            $bbox = array_map('floatval', $bbox);
            if ($isTest) {
                $query->whereRaw('ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            } else {
                $query->whereRaw('ST_Intersects(ST_Transform(geom, 4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            }
        }

        if ($adminLevel) {
            $query->where('admin_level', $adminLevel);
        }

        if ($score) {
            $query->where('score', '>=', $score);
        }

        $adminAreas = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        return response()->json($adminAreas);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/{id}",
     *     operationId="getAdminAreaById",
     *     tags={"AdminAreas"},
     *     summary="Get Admin Area by ID",
     *     description="Returns a single Admin Area in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Admin Area ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/AdminAreaGeojsonFeature")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Admin Area not found"
     *     )
     * )
     */
    public function show($id)
    {
        $adminArea = AdminArea::where('id', $id)->first();

        if (!$adminArea) {
            return response()->json(['message' => 'Admin Area non trovato'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$adminArea->geom])[0]->geojson;
        $wikidataUrl = $adminArea->getWikidata() !== null ? 'https://www.wikidata.org/wiki/' . $adminArea->getWikidata() : null;
        $wikipediaUrl = $adminArea->getWikipedia() !== null ? 'https://it.wikipedia.org/wiki/' . $adminArea->getWikipedia() : null;
        $wikimediaCommonsUrl = $adminArea->getWikimediaCommons() !== null ? 'https://commons.wikimedia.org/wiki/' . $adminArea->getWikimediaCommons() : null;


        match ($adminArea->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $adminArea->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        $properties['osm_url'] = 'https://www.openstreetmap.org/' . $osmType . '/' . $adminArea->osm_id;
        $properties['osm_api'] = 'https://www.openstreetmap.org/api/0.6/' . $osmType . '/' . $adminArea->osm_id . '.json';
        $properties['osm_tags'] = json_decode($adminArea->tags, true);
        $properties['wikipedia'] = $wikipediaUrl;
        $properties['wikidata'] = $wikidataUrl;
        $properties['wikimedia_commons'] = $wikimediaCommonsUrl;
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}