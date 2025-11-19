<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 */
Route::group(['prefix'    => 'v1', 'middleware' => 'auth:sanctum'], function() {

        // Get authenticated user
        Route::middleware('auth:sanctum')->get('/auth/user', function (Request $request) {
            return new UserResource($request->user());
        });

        // Incidents
        //Route::middleware('auth:sanctum')->apiResource('incidents', \App\Http\Controllers\Api\V1\IncidentController::class);

        // ...

});


