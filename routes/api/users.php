<?php

use App\Http\Controllers\Api\v1\Users\ChangePasswordController;
use App\Http\Controllers\Api\v1\Users\ChangeUsernameController;
use App\Http\Controllers\Api\v1\Users\ToggleUserStatusController;
use App\Http\Controllers\Api\v1\Users\UserController;
use App\Http\Controllers\Api\v1\Users\UserRoleController;
use App\Http\Controllers\Api\v1\Users\UserSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
|
| User management and profile routes grouped by access level:
| - /user prefix: Routes for authenticated users (self-management)
| - /admin prefix: Routes for administrators (user management)
|
*/

// User routes - authenticated users managing their own profile
// Rate limited: 120 requests per minute for authenticated users
Route::middleware(['auth.api', 'throttle:authenticated'])->prefix('v1/user')->group(function () {
    // User profile management
    Route::put('/me', [UserController::class, 'updateOwn'])->name('user.me.update');

    // Profile image upload - stricter rate limit (10 per minute)
    Route::post('/me/profile-image', [UserController::class, 'uploadOwnProfileImage'])
        ->middleware('throttle:uploads')
        ->name('user.me.profile-image.update');

    // User profile settings
    Route::get('/me/settings', [UserSettingController::class, 'show'])->name('user.settings.show');
    Route::put('/me/settings', [UserSettingController::class, 'update'])->name('user.settings.update');

    // User credential management (users can change their own)
    Route::put('/password', [ChangePasswordController::class, 'update'])->name('user.change-password');
    Route::put('/username', [ChangeUsernameController::class, 'update'])->name('user.change-username');

});

// Admin routes - administrators managing all users
// Rate limited: 200 requests per minute for admin operations
Route::middleware(['auth.api', 'throttle:admin'])->prefix('v1/admin')->group(function () {
    // User management (CRUD operations)
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    // User profile image management - stricter rate limit (10 per minute)
    Route::post('/users/{user}/profile-image', [UserController::class, 'uploadProfileImage'])
        ->middleware('throttle:uploads')
        ->name('admin.users.profile-image.update');

    // User role management
    Route::get('/users/{user}/roles', [UserRoleController::class, 'show'])->name('admin.users.roles.show');
    Route::post('/users/{user}/roles', [UserRoleController::class, 'store'])->name('admin.users.roles.store');
    Route::delete('/users/{user}/roles/{role}', [UserRoleController::class, 'destroy'])->name('admin.users.roles.destroy');

    // User status management (activate/deactivate)
    Route::put('/users/{user}/toggle-status', [ToggleUserStatusController::class, 'update'])->name('admin.users.toggle-status');

    // Admin credential management (can change any user's credentials)
    Route::put('/users/{user}/password', [ChangePasswordController::class, 'update'])->name('admin.users.change-password');
    Route::put('/users/{user}/username', [ChangeUsernameController::class, 'update'])->name('admin.users.change-username');
});
