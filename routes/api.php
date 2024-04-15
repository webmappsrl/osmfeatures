<?php

use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\HikingRouteController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\PoiController;
use App\Http\Controllers\PoleController;
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
    Route::get('/features/admin-areas/osm/{osmtype}/{osmid}', [AdminAreaController::class, 'osm']);
    Route::get('/features/poles/list', [PoleController::class, 'list']);
    Route::get('/features/poles/{id}', [PoleController::class, 'show']);
    Route::get('/features/poles/osm/{osmtype}/{osmid}', [PoleController::class, 'osm']);
    Route::get('/features/hiking-routes/list', [HikingRouteController::class, 'list']);
    Route::get('/features/hiking-routes/{id}', [HikingRouteController::class, 'show']);
    Route::get('/features/hiking-routes/osm/{osmtype}/{osmid}', [HikingRouteController::class, 'osm']);
    Route::get('/features/places/list', [PlaceController::class, 'list']);
    Route::get('/features/places/{id}', [PlaceController::class, 'show']);
    Route::get('/features/places/osm/{osmtype}/{osmid}', [PlaceController::class, 'osm']);
});
