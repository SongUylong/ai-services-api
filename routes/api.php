<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use Illuminate\Support\Facades\Route;

require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/conversation.php';
