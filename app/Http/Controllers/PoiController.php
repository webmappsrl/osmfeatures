<?php

namespace App\Http\Controllers;

use App\Models\Poi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;


class PoiController extends Controller
{

    public function list()
    {
        $pois = Poi::all(['osm_id', 'updated_at'])->mapWithKeys(function ($poi) {
            return [$poi->osm_id => $poi->updated_at->toIso8601String()];
        });

        return response()->json($pois);
    }


    public function show($id)
    {
        $poi = Poi::where('id', $id)->first();

        if (!$poi) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $geom = DB::select('SELECT ST_AsGeoJSON(?) AS geojson', [$poi->geom])[0]->geojson;
        $geojsonFeature = [
            'type' => 'Feature',
            'properties' => [
                'name' => $poi->name,
                'class' => $poi->class,
                'subclass' => $poi->subclass,
                'osm_id' => $poi->osm_id,
                'osm_type' => $poi->osm_type,
                'tags' => $poi->tags,
            ],
            'geometry' => json_decode($geom, true),
        ];

        return response()->json($geojsonFeature);
    }
}
