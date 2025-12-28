<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use XetaSuite\Http\Controllers\Api\V1\Auth\ForgotPasswordController;
use XetaSuite\Http\Controllers\Api\V1\Auth\SetupPasswordController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api/v1')->middleware(['web'])->group(function () {

    Route::prefix('auth')->group(function () {
        // Auth
        Route::post('/login', [AuthenticatedSessionController::class, 'store']);
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

        // Password reset (with reCAPTCHA protection)
        Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
            ->middleware('recaptcha:forgot_password');
        Route::post('/reset-password', [NewPasswordController::class, 'store']);

        // Password setup (for new users)
        Route::get('/setup-password/{id}/{hash}', [SetupPasswordController::class, 'verify'])
            ->name('auth.password.setup');
        Route::post('/setup-password/{id}/{hash}', [SetupPasswordController::class, 'store'])
            ->name('auth.password.setup.store');
        Route::post('/setup-password-resend', [SetupPasswordController::class, 'resend'])
            ->middleware('recaptcha:resend_setup_password')
            ->name('auth.password.setup.resend');
    });
});
