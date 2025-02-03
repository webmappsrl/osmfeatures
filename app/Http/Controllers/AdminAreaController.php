<?php

namespace App\Http\Controllers;

use App\Models\AdminArea;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

        $adminAreas->getCollection()->transform(function ($adminArea) {
            $adminArea->updated_at = Carbon::parse($adminArea->updated_at)->toIso8601String();
            $model = AdminArea::find($adminArea->id);
            $adminArea->id = $model->getOsmfeaturesId();

            return $adminArea;
        });

        return response()->json($adminAreas);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/{id}",
     *     operationId="getAdminAreaById",
     *     tags={"AdminAreas"},
     *     summary="Get Admin Area by osmfeatures ID",
     *     description="Returns a single Admin Area in GeoJSON format",
     *     @OA\Parameter(
     *         name="osmfeatures_id",
     *         description="Admin Area ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
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
    public function show(string $id)
    {
        $adminArea = AdminArea::getOsmfeaturesByOsmfeaturesID($id);

        if (!$adminArea) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geojsonFeature = $adminArea->getGeojsonFeature();

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/admin-areas/osm/{osmtype}/{osmid}",
     *     operationId="getAdminAreaByOsmId",
     *     tags={"AdminAreas"},
     *     summary="Get Admin Area by OSM ID",
     *     description="Returns a single Admin Area in GeoJSON format",
     *     @OA\Parameter(
     *         name="osmtype",
     *         description="OSM type (node, way, relation)",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="osmid",
     *         description="OSM ID",
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
    public function osm(string $osmType, int $osmid)
    {
        $acceptedOsmtypes = ['node', 'way', 'relation'];

        if (!in_array($osmType, $acceptedOsmtypes)) {
            return response()->json(['message' => 'Bad Request'], 404);
        }

        $adminArea = AdminArea::where('osm_type', strtoupper(substr($osmType, 0, 1)))
            ->where('osm_id', $osmid)
            ->first();

        if (!$adminArea) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$adminArea->geom])[0]->geojson;

        $properties = $adminArea->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        $properties['osm_url'] = 'https://www.openstreetmap.org/' . $osmType . '/' . $adminArea->osm_id;
        $properties['osm_api'] = 'https://www.openstreetmap.org/api/0.6/' . $osmType . '/' . $adminArea->osm_id . '.json';
        $properties['osm_tags'] = json_decode($adminArea->tags, true);
        $properties['wikipedia'] = $adminArea->getWikipediaUrl();
        $properties['wikidata'] = $adminArea->getWikidataUrl();
        $properties['wikimedia_commons'] = $adminArea->getWikimediaCommonsUrl();
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/features/admin-areas/geojson",
     *     operationId="intersectingGeojsonAdminArea",
     *     tags={"AdminAreas"},
     *     summary="Calcola e restituisce le Admin Areas che intersecano il GeoJSON fornito",
     *     description="Dati in input un oggetto GeoJSON (contenente la proprietà 'geometry') e, opzionalmente, i filtri updated_at, admin_level e score, l'API restituisce una FeatureCollection GeoJSON contenente tutte le Admin Areas le cui geometrie intersecano quella fornita. Il payload di risposta include tutte le proprietà del modello admin_area (eccetto la geometria, che viene restituita separatamente).",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Oggetto JSON contenente il campo geojson e i filtri opzionali",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"geojson"},
     *             @OA\Property(
     *                 property="geojson",
     *                 type="object",
     *                 description="Oggetto GeoJSON contenente la geometria da utilizzare per il calcolo dell'intersezione. Deve avere la struttura standard GeoJSON, con almeno il campo 'geometry'.",
     *                 example={
     *                     "type": "Feature",
     *                     "geometry": {
     *                         "type": "Polygon",
     *                         "coordinates": {
     *                             {
     *                                 {12.496366, 41.902783},
     *                                 {12.507366, 41.912783},
     *                                 {12.517366, 41.902783},
     *                                 {12.496366, 41.902783}
     *                             }
     *                         }
     *                     }
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="updated_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Filtro: restituisce solo le admin areas aggiornate dopo questa data",
     *                 example="2021-03-10T02:00:00Z"
     *             ),
     *             @OA\Property(
     *                 property="admin_level",
     *                 type="integer",
     *                 nullable=true,
     *                 description="Filtro: restituisce solo le admin areas con questo livello di amministrazione",
     *                 example=8
     *             ),
     *             @OA\Property(
     *                 property="score",
     *                 type="integer",
     *                 nullable=true,
     *                 description="Filtro: restituisce solo le admin areas con un punteggio maggiore o uguale al valore specificato",
     *                 example=3
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operazione riuscita. Restituisce una FeatureCollection in formato GeoJSON",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="FeatureCollection"
     *             ),
     *             @OA\Property(
     *                 property="features",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/GeoJsonFeature")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Errore di validazione",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Errore durante il processamento dell'intersezione GeoJSON",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function intersectingGeojson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'geojson' => 'required|array',
            'geojson.geometry' => 'required|array', // Validate geometry exists
            'geojson.geometry.type' => 'required|string|in:Point,LineString,Polygon,MultiPoint,MultiLineString,MultiPolygon', // Validate geometry type
            'geojson.geometry.coordinates' => 'required|array', // Validate coordinates exist
            'updated_at' => 'nullable|date_format:Y-m-d\TH:i:s\Z',
            'admin_level' => 'nullable|integer|min:1|max:10',
            'score' => 'nullable|integer|min:1|max:5',
        ], [
            'geojson.required' => 'The geojson field is required',
            'geojson.array' => 'The geojson field must be a valid GeoJSON object',
            'geojson.geometry.required' => 'The geometry field is required in the GeoJSON object',
            'geojson.geometry.array' => 'The geometry field must be a valid object',
            'geojson.geometry.type.required' => 'The geometry type is required',
            'geojson.geometry.type.in' => 'Invalid geometry type',
            'geojson.geometry.coordinates.required' => 'Coordinates are required',
            'geojson.geometry.coordinates.array' => 'Coordinates must be an array',
            'updated_at.date_format' => 'Invalid date format. Use ISO 8601 format (e.g. 2021-03-10T02:00:00Z)',
            'admin_level.integer' => 'The admin level must be an integer',
            'admin_level.min' => 'The admin level must be at least 1',
            'admin_level.max' => 'The admin level cannot be greater than 10',
            'score.integer' => 'The score must be an integer',
            'score.min' => 'The score must be at least 1',
            'score.max' => 'The score cannot be greater than 5'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        try {
            // Validate GeoJSON structure before processing
            if (!isset($request->geojson['geometry']) || !is_array($request->geojson['geometry'])) {
                return response()->json([
                    'message' => 'Validation errors',
                    'errors' => ['geojson' => ['Invalid GeoJSON structure']]
                ], 422);
            }

            $query = DB::table('admin_areas')
                ->whereRaw('ST_Intersects(geom, ST_GeomFromGeoJSON(?))', [json_encode($request->geojson['geometry'])])
                ->when($request->updated_at, fn($q) => $q->where('updated_at', '>=', $request->updated_at))
                ->when($request->admin_level, fn($q) => $q->where('admin_level', $request->admin_level))
                ->when($request->score, fn($q) => $q->where('score', '>=', $request->score));

            $results = $query->get();

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $results->map(function ($adminArea) {
                    return [
                        'type' => 'Feature',
                        'properties' => collect($adminArea)->except(['geom'])->all(),
                        'geometry' => DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$adminArea->geom])[0]->geojson,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error processing GeoJSON intersection',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
