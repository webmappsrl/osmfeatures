<?php

namespace App\Http\Controllers;

use App\Models\Poi;
use Illuminate\Http\Response;

class PoiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/features/pois/list",
     *     operationId="listPois",
     *     tags={"POIs"},
     *     summary="List all POIs",
     *     description="Returns a list of POIs with their IDs and updated timestamps",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Poi")
     *         ),
     *     ),
     * )
     */
    public function list()
    {
        $pois = Poi::all(['osm_id', 'updated_at'])->mapWithKeys(function ($poi) {
            return [$poi->osm_id => $poi->updated_at];
        });

        return response()->json($pois);
    }

    public function show($id)
    {
        $poi = Poi::find($id);

        if (!$poi) {
            return response()->json(['message' => 'POI non trovato'], 404);
        }

        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => [
                'name' => $poi->name,
                'class' => $poi->class,
                'subclass' => $poi->subclass,
                'osm_id' => $poi->osm_id,
                'osm_type' => $poi->osm_type,
            ],
            'geometry' => json_decode($poi->geom) // Assumendo che 'geom' sia già in formato GeoJSON
        ];

        return response()->json($geojsonFeature);
    }
}
