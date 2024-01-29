<?php

use App\Http\Controllers\AdminAreaController;
use App\Http\Controllers\PoiController;
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
});
