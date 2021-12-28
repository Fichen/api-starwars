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

Route::fallback(function(){
    return response()->json(['detail' => 'Not Found'], 404);
});

 Route::get('starships/{id}', StarshipController::class . '@show')->name('starships.show');

 Route::get('starships', StarshipController::class . '@index')->name('starships.index');

 Route::patch('starships/{id}', StarshipController::class . '@update')->name('starships.update');
 Route::patch('starships/increment/{id}', StarshipController::class . '@increment')->name('starships.increment');
 Route::patch('starships/decrement/{id}', StarshipController::class . '@decrement')->name('starships.decrement');
