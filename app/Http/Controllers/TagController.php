<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TagController extends Controller
{
    public function details($resource, $resourceId)
    {
        $resource = ucfirst($resource);
        $model = "App\\Models\\$resource";
        $resource = $model::findOrFail($resourceId);
        $tags = $resource->tags;
        $tagsArray = json_decode($tags, true);
        $tagsPretty = json_encode($tagsArray, JSON_PRETTY_PRINT);
        return view('tags-details', compact('tagsPretty'));
    }
}
