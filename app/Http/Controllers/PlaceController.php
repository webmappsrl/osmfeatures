<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="OSM features API",
 * description="The OSM Features API provides a comprehensive suite of endpoints for accessing OpenStreetMap (OSM) features in GeoJSON format, designed to streamline the integration and utilization of OSM data in applications. By leveraging daily syncs from the OSM database through osm2pgsql imports, the API ensures the delivery of up-to-date and efficiently processed data. Endpoints include retrieving lists and specific details of Places, Administrative Areas, Hiking Routes, and Poles, each identifiable by unique osmfeatures IDs and accompanied by their latest update timestamps. The API allows for fetching single items or lists, providing data in GeoJSON format. Every endpoint is provided with detailed descriptions, parameters and responses.",
 * @OA\Contact(
 * email="info@webmapp.it"
 * )
 * )
 */
class PlaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/places/list",
     *     operationId="listPlaces",
     *     tags={"API V1"},
     *     summary="List all Places",
     *     description="Returns a list of Places with their IDs and updated timestamps. Optionally filtered by updated_at, bbox and score. Paginated results are available.",
     *
     *     @OA\Parameter(ref="#/components/parameters/list_updated_at"),
     *     @OA\Parameter(ref="#/components/parameters/list_page"),
     *     @OA\Parameter(ref="#/components/parameters/list_bbox"),
     *     @OA\Parameter(ref="#/components/parameters/list_score"),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/BaseListItem")
     *             }
     *         )
     *     )
     * )
     */
    public function list(Request $request)
    {
        $updated_at = $request->query('updated_at');
        $perPage = 1000;
        $bbox = $request->query('bbox');
        $score = $request->query('score');
        $isTest = $request->query('testdata');

        $query = DB::table('places');

        if ($updated_at) {
            $query->where('updated_at', '>', $updated_at);
        }

        if ($bbox) {
            $bbox = explode(',', $bbox);
            // Check if the bbox is valid
            if (count($bbox) !== 4) {
                return response()->json(['message' => 'Bounding box non valido'], 400);
            }
            $bbox = array_map('floatval', $bbox);
            if ($isTest) {
                $query->whereRaw('ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            } else {
                $query->whereRaw('ST_Intersects(ST_Transform(geom, 4326), ST_MakeEnvelope(?, ?, ?, ?, 4326))', [$bbox[0], $bbox[1], $bbox[2], $bbox[3]]);
            }
        }

        if ($score) {
            $query->where('score', '>=', $score);
        }

        $places = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at', 'osm_type', 'osm_id']);

        $places->getCollection()->transform(function ($place) {
            $place->updated_at = Carbon::parse($place->updated_at)->toIso8601String();
            $place->id = $place->osm_type . $place->osm_id;
            unset($place->osm_type, $place->osm_id);

            return $place;
        });

        return response()->json($places);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/places/{id}",
     *     operationId="getPlaceById",
     *     tags={"API V1"},
     *     summary="Get Place by Osmfeatures ID",
     *     description="Returns a single Place in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Place Osmfeatures ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", example="Feature"),
     *             @OA\Property(
     *                 property="properties",
     *                 type="object",
     *                 @OA\Property(property="osm_type", type="string", example="N"),
     *                 @OA\Property(property="osm_id", type="integer", example=123456),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2021-01-01T00:00:00Z"),
     *                 @OA\Property(property="name", type="string", example="Place"),
     *                 @OA\Property(property="class", type="string", example="amenity"),
     *                 @OA\Property(property="subclass", type="string", example="restaurant"),
     *                 @OA\Property(property="elevation", type="number", example=1000),
     *                 @OA\Property(property="score", type="number", example=1),
     *                 @OA\Property(property="osmfeatures_id", type="string", example="N123456"),
     *                 @OA\Property(property="osm_url", type="string", example="https://www.openstreetmap.org/node/123456"),
     *                 @OA\Property(property="osm_api", type="string", example="https://www.openstreetmap.org/api/0.6/node/1952252737.json"),
     *                 @OA\Property(
     *                     property="osm_tags",
     *                     type="object",
     *                     example={"amenity": "restaurant"}
     *                 ),
     *                 @OA\Property(property="wikidata", type="string", example="https://www.wikidata.org/wiki/Q123456"),
     *                 @OA\Property(property="wikipedia", type="string", example="https://en.wikipedia.org/wiki/Example"),
     *                 @OA\Property(property="wikimedia_commons", type="string", example="https://commons.wikimedia.org/wiki/Category:Example"),
     *                 @OA\Property(
     *                     property="enriched_data",
     *                     type="object",
     *                     example={
     *                         "id": 1,
     *                         "last_update_wikipedia": "https://en.wikipedia.org/wiki/Example",
     *                         "last_update_wikidata": "https://www.wikidata.org/wiki/Q123456",
     *                         "last_update_wikimedia_commons": "https://commons.wikimedia.org/wiki/Category:Example",
     *                         "abstract": {
     *                             "it": "Questo é un abstract",
     *                             "en": "This is an abstract"
     *                         },
     *                         "description": {
     *                             "it": "Questa é una descrizione",
     *                             "en": "This is a description"
     *                         },
     *                         "images": {
     *                             "0": {
     *                                 "source_url": "https://wikicommons.example.com/image.jpg",
     *                                 "dateTime": "2021-01-01T00:00:00Z",
     *                                 "aws_url": "https://aws.example.com/image.jpg"
     *                             }
     *                         }
     *                     }
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="geometry",
     *                 type="object",
     *                 @OA\Property(property="type", type="string", example="Point"),
     *                 @OA\Property(property="coordinates", type="array", example={100, 0}, @OA\Items(type="number"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Place not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $place = Place::getOsmfeaturesByOsmfeaturesID($id);

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }

        $geojsonFeature = $place->getGeojsonFeature();

        return response()->json($geojsonFeature);
    }


    public function osm(string $osmType, int $osmid)
    {
        $acceptedOsmtypes = ['node', 'way', 'relation'];

        if (!in_array($osmType, $acceptedOsmtypes)) {
            return response()->json(['message' => 'Bad Request'], 404);
        }

        $place = Place::where('osm_type', strtoupper(substr($osmType, 0, 1)))->where('osm_id', $osmid)->first();

        if (!$place) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$place->geom])[0]->geojson;

        $properties = $place->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$place->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$place->osm_id.json";
        $properties['osm_tags'] = json_decode($place->tags, true);
        $properties['wikidata'] = $place->getWikidataUrl();
        $properties['wikipedia'] = $place->getWikipediaUrl();
        $properties['wikimedia_commons'] = $place->getWikimediaCommonsUrl();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/places/{lon}/{lat}/{distance}",
     *     operationId="getPlacesByDistance",
     *     tags={"API V1"},
     *     summary="Get nearby places",
     *     description="Get places within a specified distance from the given coordinates.",
     *     @OA\Parameter(
     *         name="lon",
     *         description="Longitude of the location",
     *         example="10.494953",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="lat",
     *         description="Latitude of the location",
     *         example="46.179482",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="distance",
     *         description="Distance in meters",
     *         example=1000,
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "osmfeatures_id": "W195710674",
     *                 "name": "Rifugio Giuseppe Garibaldi",
     *                 "class": "tourism",
     *                 "subclass": "alpine_hut",
     *                 "elevation": 2548,
     *                 "distance": 9
     *             }
     *         )
     *     )
     * )
     */
    /**
     * Get Places by distance
     *
     * Returns a list of Places within the specified distance from the given coordinates.
     *
     * @param string $lon Longitude
     * @param string $lat Latitude
     * @param int    $distance Distance in meters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlacesByDistance(string $lon, string $lat, int $distance)
    {
        // Validate parameters
        if (!is_numeric($lon)) {
            return response()->json(['message' => 'Invalid longitude parameter'], 400);
        }

        if (!is_numeric($lat)) {
            return response()->json(['message' => 'Invalid latitude parameter'], 400);
        }

        if (!is_numeric($distance)) {
            return response()->json(['message' => 'Invalid distance parameter'], 400);
        }

        try {
            // Build the SQL query
            $places = DB::table('places')
                ->select(DB::raw("
        osm_type || osm_id AS osmfeatures_id,
        name,
        class,
        subclass,
        elevation,
        ROUND(
            ST_Distance(
                ST_Transform(geom::geometry, 3857),
                ST_Transform(
                    ST_SetSRID(ST_MakePoint(?, ?), 4326),
                    3857
                )
            )
        )::integer AS distance
    "))
                ->whereRaw("
        ST_Distance(
            ST_Transform(geom::geometry, 3857),
            ST_Transform(
                ST_SetSRID(ST_MakePoint(?, ?), 4326),
                3857
            )
        ) < ?
    ", [$lon, $lat, $lon, $lat, $distance])
                ->orderBy('distance')
                ->get();
        } catch (\Exception $e) {
            // Log and return error response on exception
            Log::error($e->getMessage());
            return response()->json(['message' => 'Bad Request'], 400);
        }

        // Return success response
        return response()->json($places);
    }
}
