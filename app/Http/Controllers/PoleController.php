<?php

namespace App\Http\Controllers;

use App\Models\Pole;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/list",
     *     operationId="listPoles",
     *     tags={"API V1"},
     *     summary="List all Poles",
     *     description="Returns a list of Poles with their IDs and updated timestamps. Optionally provide an 'updated_at' and bbox parameter to filter poles. Paginated results are available, with each page containing 1000 poles. Use the 'page' parameter to specify the page number to retrieve.",
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
        $updated_after = $request->query('updated_at');
        $perPage = 1000;
        $bbox = $request->query('bbox');
        $score = $request->query('score');
        $query = DB::table('poles');
        $isTest = $request->query('testdata');

        if ($updated_after) {
            $query->where('updated_at', '>', $updated_after);
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

        $poles = $query->orderBy('updated_at', 'desc')->paginate($perPage, ['id', 'updated_at']);

        $poles->getCollection()->transform(function ($pole) {
            $pole->updated_at = Carbon::parse($pole->updated_at)->toIso8601String();
            $model = Pole::find($pole->id);
            $pole->id = $model->getOsmfeaturesId();

            return $pole;
        });

        return response()->json($poles);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/features/poles/{id}",
     *     operationId="getPoleById",
     *     tags={"API V1"},
     *     summary="Get Pole by Osmfeatures ID",
     *     description="Returns a single Pole in GeoJSON format",
     *     @OA\Parameter(
     *         name="id",
     *         description="Pole osmfeatures ID",
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="type", type="string", description="Type of the GeoJSON object"),
     *             @OA\Property(
     *                 property="properties",
     *                 type="object",
     *                 @OA\Property(property="osm_type", type="string", description="Type of the OSM object (N, W, R)"),
     *                 @OA\Property(property="osm_id", type="integer", description="ID of the OSM object"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", description="When the pole was last updated in OSM, in ISO 8601 format."),
     *                 @OA\Property(property="name", type="string", description="Name of the pole"),
     *                 @OA\Property(property="ref", type="string", description="Reference number of the pole"),
     *                 @OA\Property(property="ele", type="number", description="Elevation of the pole in meters"),
     *                 @OA\Property(property="destination", type="string", description="Destination of the pole"),
     *                 @OA\Property(property="support", type="string", description="Support of the pole"),
     *                 @OA\Property(property="score", type="number", description="Score of the pole"),
     *                 @OA\Property(property="osmfeatures_id", type="string", description="Osmfeatures ID of the pole"),
     *                 @OA\Property(property="osm_url", type="string", description="URL to the Openstreetmap corresponding feature."),
     *                 @OA\Property(property="osm_api", type="string", description="URL to the OSM API for the object"),
     *                 @OA\Property(property="osm_tags", type="object", description="OSM tags of the object"),
     *                 @OA\Property(property="wikidata", type="string", description="Wikidata link for the pole"),
     *                 @OA\Property(property="wikipedia", type="string", description="Wikipedia link for the pole"),
     *                 @OA\Property(property="wikimedia_commons", type="string", description="Wikimedia Commons link for the pole")
     *             ),
     *             @OA\Property(property="geometry", type="object", description="Geometry of the pole in GeoJSON format")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pole not found"
     *     )
     * )
     */
    public function show(string $id)
    {
        $pole = Pole::getOsmfeaturesByOsmfeaturesID($id);

        if (!$pole) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geojsonFeature = $pole->getGeojsonFeature();

        return response()->json($geojsonFeature);
    }


    public function osm(string $osmType, int $osmid)
    {
        $acceptedTypes = ['relation', 'way', 'node'];

        if (!in_array($osmType, $acceptedTypes)) {
            return response()->json(['message' => 'Bad request'], 404);
        }

        $pole = Pole::where('osm_type', strtoupper(substr($osmType, 0, 1)))
            ->where('osm_id', $osmid)
            ->first();

        if (!$pole) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$pole->geom])[0]->geojson;
        match ($pole->osm_type) {
            'R' => $osmType = 'relation',
            'W' => $osmType = 'way',
            'N' => $osmType = 'node',
        };

        $properties = $pole->toArray();
        unset($properties['geom']);
        unset($properties['tags']);
        unset($properties['elevation']);
        $properties['osm_url'] = $pole->getOsmUrl();
        $properties['osm_api'] = $pole->getOsmApiUrl();
        $properties['osm_tags'] = json_decode($pole->tags, true);
        $properties['wikidata'] = $pole->getWikidataUrl();
        $properties['wikipedia'] = $pole->getWikipediaUrl();
        $properties['wikimedia_commons'] = $pole->getWikimediaCommonsUrl();

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => $properties,
            'geometry' => json_decode($geom),
        ];

        return response()->json($geojsonFeature);
    }
}
