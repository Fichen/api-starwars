<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StarshipController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* Route::resource('starships', StarshipController::class, [
    'except' => ['create', 'destroy']
]);
 */

 Route::get('starships/{id}', StarshipController::class . '@show')->name('starships.show');

 Route::get('starships', StarshipController::class . '@index')->name('starships.index');
