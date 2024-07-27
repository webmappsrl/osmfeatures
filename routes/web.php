<?php

use App\Jobs\TestHorizonJob;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TagController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/tags-details/{resource}/{resourceId}', [TagController::class, 'details'])->name('tags-details');

Route::get('/test-horizon', function () {
    for ($i = 0; $i < 10000; $i++) {
        TestHorizonJob::dispatch();
        sleep(1);
    }

    return 'Dispatched 1000 jobs';
});
