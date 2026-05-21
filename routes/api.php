<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ModeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/google/callback', [AuthController::class, 'googleBrowserCallback']);
    Route::post('/google/callback', [AuthController::class, 'googleCallback']);
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
        ->middleware('throttle:6,1');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed:relative', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/audios/{audio}/stream', [AudioController::class, 'stream'])
    ->middleware('signed')
    ->name('audios.stream');
Route::get('/modes/{mode}/audios', [AudioController::class, 'byMode']);
Route::get('/modes/all', [ModeController::class, 'getAll']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/audios/all', [AudioController::class, 'getAll']);
    Route::apiResource('audios', AudioController::class);
    Route::apiResource('flows', FlowController::class);
    Route::apiResource('items', ItemController::class)->only(['index', 'show']);
    Route::apiResource('modes', ModeController::class);
    Route::apiResource('profiles', ProfileController::class)->only(['index', 'show']);
    Route::get('/users/all', [UserController::class, 'getAll']);
    Route::get('/users/search', [UserController::class, 'search']);
    Route::apiResource('users', UserController::class);
    Route::get('/user', [AuthController::class, 'currentUser']);
});
