<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This backend is API-only. The root route returns a simple JSON response
| to indicate the API is operational.
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'AI Services API is running',
        'version' => '1.0.0'
    ]);
});