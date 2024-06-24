<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/places/list",
     *     operationId="listPlaces",
     *     tags={"Places"},
     *     summary="List all Places",
     *     description="Returns a list of Places with their details. Optionally, provide an 'updated_at' parameter to filter places updated after the specified date.",
     *     @OA\Parameter(
     *         name="updated_at",
     *         in="query",
     *         description="Filter by the updated timestamp. Only places updated after this date will be returned. The date should be in ISO 8601 format.",
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
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/PlaceItem")
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        $updated_at = $request->query('updated_at');
        $perPage = 100;
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

        $places = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        $places->getCollection()->transform(function ($place) {
            $place->updated_at = Carbon::parse($place->updated_at)->toIso8601String();
            $model = Place::find($place->id);
            $place->id = $model->getOsmFeaturesId();

            return $place;
        });

        return response()->json($places);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/places/{id}",
     *     operationId="getPlaceById",
     *     tags={"Places"},
     *     summary="Get Place by osmfeatures ID",
     *     description="Returns a single Place in GeoJSON format",
     *     @OA\Parameter(
     *         name="osmfeatures_id",
     *         description="Place osmfeatures ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
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
            return response()->json(['message' => 'place non trovato'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$place->geom])[0]->geojson;

        $enrichment = json_decode($place->enrichment, true);
        $enrichment['data'] = json_decode($enrichment['data'], true);

        match ($place->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $place->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        unset($properties['id']);
        unset($properties['enrichment']);
        $properties['osmfeatures_id'] = $id;
        $properties['osm_url'] = "https://www.openstreetmap.org/$osmType/$place->osm_id";
        $properties['osm_api'] = "https://www.openstreetmap.org/api/0.6/$osmType/$place->osm_id.json";
        $properties['osm_tags'] = json_decode($place->tags, true);
        $properties['wikidata'] = $place->getWikidataUrl();
        $properties['wikipedia'] = $place->getWikipediaUrl();
        $properties['wikimedia_commons'] = $place->getWikimediaCommonsUrl();
        $properties['enriched_data'] = $enrichment;

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return response()->json($geojsonFeature);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/places/osm/{osmtype}/{osmid}",
     *     operationId="getPlaceByOsmId",
     *     tags={"Places"},
     *     summary="Get Place by OSM ID",
     *     description="Returns a single Place in GeoJSON format",
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Place not found"
     *     )
     * )
     */
    public function osm(string $osmType, int $osmid)
    {
        $acceptedOsmtypes = ['node', 'way', 'relation'];

        if (!in_array($osmType, $acceptedOsmtypes)) {
            return response()->json(['message' => 'Bad Request'], 404);
        }

        $place = Place::where('osm_type', strtoupper(substr($osmType, 0, 1)))->where('osm_id', $osmid)->first();

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
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
}