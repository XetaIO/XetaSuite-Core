<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use XetaSuite\Http\Controllers\Api\V1\ItemController;
use XetaSuite\Http\Controllers\Api\V1\MaterialController;
use XetaSuite\Http\Controllers\Api\V1\SiteController;
use XetaSuite\Http\Controllers\Api\V1\SupplierController;
use XetaSuite\Http\Controllers\Api\V1\UserLocaleController;
use XetaSuite\Http\Controllers\Api\V1\UserSiteController;
use XetaSuite\Http\Controllers\Api\V1\ZoneController;
use XetaSuite\Http\Resources\V1\Users\UserDetailResource;
use XetaSuite\Models\User;

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

    // Sites (headquarters only - enforced by SitePolicy)
    Route::apiResource('sites', SiteController::class);
    Route::get('sites/{site}/users', [SiteController::class, 'users']);
    Route::get('sites/{site}/members', [SiteController::class, 'members']);

    // Suppliers (headquarters only - enforced by SupplierPolicy)
    Route::apiResource('suppliers', SupplierController::class);
    Route::get('suppliers/{supplier}/items', [SupplierController::class, 'items']);

    // Zones (site-scoped - enforced by ZonePolicy)
    Route::get('zones/available-parents', [ZoneController::class, 'availableParents']);
    Route::apiResource('zones', ZoneController::class);
    Route::get('zones/{zone}/children', [ZoneController::class, 'children']);
    Route::get('zones/{zone}/materials', [ZoneController::class, 'materials']);

    // Materials (site-scoped - enforced by MaterialPolicy)
    Route::get('materials/available-zones', [MaterialController::class, 'availableZones']);
    Route::get('materials/available-recipients', [MaterialController::class, 'availableRecipients']);
    Route::apiResource('materials', MaterialController::class);
    Route::get('materials/{material}/stats', [MaterialController::class, 'stats']);

    // Items (site-scoped - enforced by ItemPolicy)
    Route::get('items/available-suppliers', [ItemController::class, 'availableSuppliers']);
    Route::get('items/available-materials', [ItemController::class, 'availableMaterials']);
    Route::get('items/available-recipients', [ItemController::class, 'availableRecipients']);
    Route::get('items-dashboard', [ItemController::class, 'dashboard']);
    Route::apiResource('items', ItemController::class);
    Route::get('items/{item}/stats', [ItemController::class, 'stats']);
    Route::get('items/{item}/movements', [ItemController::class, 'movements']);
    Route::post('items/{item}/movements', [ItemController::class, 'storeMovement']);
    Route::get('items/{item}/qr-code', [ItemController::class, 'qrCode']);

    // Incidents
    // Route::middleware('auth:sanctum')->apiResource('incidents', \App\Http\Controllers\Api\V1\IncidentController::class);

    // ...

});
