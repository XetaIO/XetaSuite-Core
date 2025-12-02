<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use XetaSuite\Http\Controllers\Api\V1\SiteController;
use XetaSuite\Http\Controllers\Api\V1\SupplierController;
use XetaSuite\Http\Controllers\Api\V1\UserLocaleController;
use XetaSuite\Http\Controllers\Api\V1\UserSiteController;
use XetaSuite\Http\Resources\V1\Items\ItemResource;
use XetaSuite\Http\Resources\V1\Users\UserDetailResource;

/*
 |--------------------------------------------------------------------------
 | API Routes
 |--------------------------------------------------------------------------
 */
Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function () {

    // Get authenticated user
    Route::get('/auth/user', function (Request $request) {
        return new UserDetailResource($request->user());
    });

    // Update user locale
    Route::patch('/user/locale', UserLocaleController::class);

    // Update user current site
    Route::patch('/user/site', UserSiteController::class);

    /*Route::get('/items/{item}', function (\XetaSuite\Models\Item $item) {
        return new ItemResource($item);
    });*/

    // Sites (headquarters only - enforced by SitePolicy)
    Route::apiResource('sites', SiteController::class);

    // Suppliers (headquarters only - enforced by SupplierPolicy)
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('suppliers/{supplier}/items', [SupplierController::class, 'items']);

    // Incidents
    // Route::middleware('auth:sanctum')->apiResource('incidents', \App\Http\Controllers\Api\V1\IncidentController::class);

    // ...

});
