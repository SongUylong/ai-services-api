<?php

use App\Http\Controllers\Api\v1\Auth\AuthUserController;
use App\Http\Controllers\Api\v1\Auth\LoginController;
use App\Http\Controllers\Api\v1\Auth\LogoutController;
use App\Http\Controllers\Api\v1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\v1\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Authentication and authorization routes
|
*/

// Public auth routes (v1) - No prefix, available to everyone
// Rate limited: 5 requests per minute to prevent brute force attacks
Route::prefix('v1')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register'])->name('auth.register');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
    Route::post('/refresh-token', RefreshTokenController::class)->name('auth.refresh-token');
});

// User auth routes (authenticated users only)
// Rate limited: 120 requests per minute for authenticated users
Route::middleware(['auth.api', 'throttle:authenticated'])->prefix('v1/user')->group(function () {
    //get authenticated user info
    Route::get('/me', [AuthUserController::class, 'show'])->name('user.me');
    Route::post('/logout', [LogoutController::class, 'logout'])->name('user.logout');
    Route::post('/logout-all-devices', [LogoutController::class, 'logoutAllDevices'])->name('user.logout-all-devices');
});
