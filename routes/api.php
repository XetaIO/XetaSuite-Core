<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use XetaSuite\Http\Resources\V1\Users\UserResource;

/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 */
Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function () {

    // Get authenticated user
    Route::get('/auth/user', function (Request $request) {
        return new UserResource($request->user());
    });

    // Incidents
    // Route::middleware('auth:sanctum')->apiResource('incidents', \App\Http\Controllers\Api\V1\IncidentController::class);

    // ...

});
