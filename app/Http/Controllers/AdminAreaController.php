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
     *     tags={"API V1"},
     *     summary="List all Admin Areas",
     *     description="Returns a list of Admin Areas with their details. Optionally, filtered by updated_at, bbox, admin_level and score. Paginated results are available.",
     *
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
     *
     *     @OA\Parameter(
     *         name="admin_level",
     *         in="query",
     *         description="Administrative level of the area.",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             example="8"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="osmfeatures_id",
     *                 type="string",
     *                 description="Osmfeatures ID of the area"
     *             ),
     *             @OA\Property(
     *                 property="updated_at",
     *                 type="string",
     *                 format="date-time",
     *                 description="When the admin area was last updated in OSM, in ISO 8601 format."
     *             ),
     *             example={
     *                 "id": "R123456",
     *                 "updated_at": "2021-01-01T00:00:00Z"
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error processing GeoJSON intersection"
     *     )
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
     *     tags={"API V1"},
     *     summary="Get Admin Area by Osmfeatures ID",
     *     description="Returns a single Admin Area in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Admin Area Osmfeatures ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="type", type="string", description="Type of the GeoJSON object"),
     *                 @OA\Property(
     *                     property="properties",
     *                     type="object",
     *                     properties={
     *                         @OA\Property(property="osm_type", type="string", description="Type of the OSM object (N, W, R)"),
     *                         @OA\Property(property="osm_id", type="integer", description="ID of the OSM object"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", description="When the area was last updated in OSM, in ISO 8601 format."),
     *                         @OA\Property(property="name", type="string", description="Name of the area"),
     *                         @OA\Property(property="admin_level", type="string", description="Administrative level of the area"),
     *                         @OA\Property(property="score", type="integer", description="Score of the area"),
     *                         @OA\Property(property="osmfeatures_id", type="string", description="Osmfeatures ID of the area"),
     *                         @OA\Property(property="osm_url", type="string", description="URL to the Openstreetmap corresponding feature."),
     *                         @OA\Property(property="osm_api", type="string", description="URL to the OSM API for the object"),
     *                         @OA\Property(property="osm_tags", type="object", description="OSM tags of the object"),
     *                         @OA\Property(property="wikipedia", type="string", description="Wikipedia link for the area"),
     *                         @OA\Property(property="wikidata", type="string", description="Wikidata link for the area"),
     *                         @OA\Property(property="wikimedia_commons", type="string", description="Wikimedia Commons link for the area")
     *                     }
     *                 ),
     *                 @OA\Property(property="geometry", type="object", description="Geometry of the area in GeoJSON format")
     *             },
     *             example={
     *                 "type": "Feature",
     *                 "properties": {
     *                     "osm_type": "R",
     *                     "osm_id": 123456,
     *                     "updated_at": "2021-01-01T00:00:00Z",
     *                     "name": "Admin Area",
     *                     "admin_level": "6",
     *                     "score": 1,
     *                     "osmfeatures_id": "R123456",
     *                     "osm_url": "https://www.openstreetmap.org/relation/123456",
     *                     "osm_api": "https://www.openstreetmap.org/api/0.6/node/1952252737.json",
     *                     "osm_tags": {
     *                         "type": "boundary",
     *                         "boundary": "administrative"
     *                     },
     *                     "wikipedia": "https://en.wikipedia.org/wiki/Example",
     *                     "wikidata": "https://www.wikidata.org/wiki/Q123456", 
     *                     "wikimedia_commons": "https://commons.wikimedia.org/wiki/Category:Example"
     *                 },
     *                 "geometry": {
     *                     "type": "Polygon",
     *                     "coordinates": {
     *                         {
     *                             {100.0, 0.0},
     *                             {101.0, 0.0},
     *                             {101.0, 1.0},
     *                             {100.0, 1.0},
     *                             {100.0, 0.0}
     *                         }
     *                     }
     *                 }
     *             }
     *         )
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
     *     tags={"API V1"},
     *     summary="Calculate and return Admin Areas that intersect the provided GeoJSON",
     *     description="Given a GeoJSON object containing the 'geometry' property and, optionally, the filters updated_at, admin_level and score, the API returns a GeoJSON FeatureCollection containing all Admin Areas whose geometries intersect the provided one. The response payload includes all properties of the admin_area model.",
     *     @OA\RequestBody(
     *         required=true,
     *         content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     type="object",
     *                     required={"geojson"},
     *                     @OA\Property(
     *                         property="geojson",
     *                         type="object",
     *                         @OA\Property(property="type", type="string", example="Feature"),
     *                         @OA\Property(
     *                             property="geometry",
     *                             type="object",
     *                             @OA\Property(property="type", type="string", example="MultiLineString"),
     *                             @OA\Property(
     *                                 property="coordinates",
     *                                 type="array",
     *                                 @OA\Items(type="array", @OA\Items(type="array", @OA\Items(type="number")))
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="properties",
     *                             type="object",
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="updated_at",
     *                         type="string",
     *                         description="Admin Area updated after this date",
     *                         example="2016-01-01T00:00:00Z"
     *                     ),
     *                     @OA\Property(
     *                         property="admin_level",
     *                         type="integer",
     *                         description="Admin Area admin_level",
     *                         example=8
     *                     ),
     *                     @OA\Property(
     *                         property="score",
     *                         type="integer", 
     *                         description="Admin Area score",
     *                         example=2
     *                     ),
     *                     example={
     *                         "geojson": {
     *                             "type": "Feature",
     *                             "geometry": {
     *                                 "type": "MultiLineString",
     *                                 "coordinates": {
     *                                     {
     *                                         {10.6955, 43.8535},
     *                                         {10.697, 43.8525},
     *                                         {10.699, 43.8515},
     *                                         {10.701, 43.8505},
     *                                         {10.703, 43.8495}
     *                                     },
     *                                     {
     *                                         {10.704, 43.849},
     *                                         {10.706, 43.848},
     *                                         {10.708, 43.847},
     *                                         {10.710, 43.846},
     *                                         {10.712, 43.845}
     *                                     }
     *                                 }
     *                             },
     *                             "properties": {
     *                                 "name": "Hiking Route Intersecting"
     *                             }
     *                         },
     *                         "updated_at": "2016-01-01T00:00:00Z",
     *                         "admin_level": 8,
     *                         "score": 2
     *                     }
     *                 )
     *             )
     *         }
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="FeatureCollection"),
     *             @OA\Property(
     *                 property="features",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="type", type="string", example="Feature"),
     *                     @OA\Property(
     *                         property="properties",
     *                         type="object",
     *                         @OA\Property(property="osm_type", type="string", example="R"),
     *                         @OA\Property(property="osm_id", type="integer", example=42652),
     *                         @OA\Property(property="id", type="integer", example=2),
     *                         @OA\Property(property="updated_at", type="string", example="2016-12-03 05:53:02"),
     *                         @OA\Property(property="name", type="string", example="Chiesina Uzzanese"),
     *                         @OA\Property(property="tags", type="string", example="{""name"": ""Chiesina Uzzanese"", ""type"": ""boundary"", ""boundary"": ""administrative"", ""wikidata"": ""Q102578"", ""ref:ISTAT"": ""047022"", ""wikipedia"": ""it:Chiesina Uzzanese"", ""admin_level"": ""8"", ""ref:catasto"": ""C631""}"),
     *                         @OA\Property(property="admin_level", type="integer", example=8),
     *                         @OA\Property(property="score", type="integer", example=3)
     *                     ),
     *                     @OA\Property(
     *                         property="geometry",
     *                         type="object",
     *                         @OA\Property(property="type", type="string", example="MultiPolygon"),
     *                         @OA\Property(
     *                             property="coordinates",
     *                             type="array",
     *                             @OA\Items(type="array", @OA\Items(type="array", @OA\Items(type="array", @OA\Items(type="number"))))
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error processing GeoJSON intersection"
     *     )
     * )
     */
    public function intersectingGeojson(Request $request)
    {
        // Check that only allowed parameters are present
        $allowedParams = ['geojson', 'updated_at', 'admin_level', 'score'];
        $inputParams = array_keys($request->all());
        $invalidParams = array_diff($inputParams, $allowedParams);

        if (!empty($invalidParams)) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => ['invalid_parameters' => 'The following parameters are not allowed: ' . implode(', ', $invalidParams)]
            ], 422);
        }

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
