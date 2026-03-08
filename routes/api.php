<?php

use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\HikingRouteController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\PoleController;
use App\Http\Controllers\V2\AdminAreaController as AdminAreaControllerV2;
use App\Http\Controllers\V2\HikingRouteController as HikingRouteControllerV2;
use App\Http\Controllers\V2\PlaceController as PlaceControllerV2;
use App\Http\Controllers\V2\PoiController as PoiControllerV2;
use App\Http\Controllers\V2\PoleController as PoleControllerV2;
use App\Http\Controllers\V2\RefreshController as RefreshControllerV2;
use App\Http\Controllers\V2\SearchController as SearchControllerV2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('/features/pois/list', [PoiController::class, 'list']);
    Route::get('/features/pois/{id}', [PoiController::class, 'show']);
    Route::get('/features/admin-areas/list', [AdminAreaController::class, 'list']);
    Route::get('/features/admin-areas/{id}', [AdminAreaController::class, 'show']);
    Route::post('/features/admin-areas/geojson', [AdminAreaController::class, 'intersectingGeojson']);
    Route::get('/features/poles/list', [PoleController::class, 'list']);
    Route::get('/features/poles/{id}', [PoleController::class, 'show']);
    Route::get('/features/hiking-routes/list', [HikingRouteController::class, 'list']);
    Route::get('/features/hiking-routes/{id}', [HikingRouteController::class, 'show']);
    Route::get('/features/places/list', [PlaceController::class, 'list']);
    Route::get('/features/places/{id}', [PlaceController::class, 'show']);
    Route::get('/features/places/{lon}/{lat}/{distance}', [PlaceController::class, 'getPlacesByDistance']);
    Route::get('/features/search', '\App\Http\Controllers\SearchController@search');
})->middleware('throttle:api');

Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::get('/features/pois/list', [PoiControllerV2::class, 'list']);
    Route::get('/features/pois/{id}', [PoiControllerV2::class, 'show']);
    Route::get('/features/admin-areas/list', [AdminAreaControllerV2::class, 'list']);
    Route::get('/features/admin-areas/{id}', [AdminAreaControllerV2::class, 'show']);
    Route::post('/features/admin-areas/geojson', [AdminAreaControllerV2::class, 'intersectingGeojson']);
    Route::get('/features/poles/list', [PoleControllerV2::class, 'list']);
    Route::get('/features/poles/{id}', [PoleControllerV2::class, 'show']);
    Route::get('/features/hiking-routes/list', [HikingRouteControllerV2::class, 'list']);
    Route::get('/features/hiking-routes/{id}', [HikingRouteControllerV2::class, 'show']);
    Route::get('/features/places/list', [PlaceControllerV2::class, 'list']);
    Route::get('/features/places/{id}', [PlaceControllerV2::class, 'show']);
    Route::get('/features/places/{lon}/{lat}/{distance}', [PlaceControllerV2::class, 'getPlacesByDistance']);
    Route::get('/features/search', [SearchControllerV2::class, 'search']);
    Route::get('/features/refresh/{id}', [RefreshControllerV2::class, 'refresh']);
})->middleware('throttle:api');
