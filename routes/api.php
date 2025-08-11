<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

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

// Public routes (tidak perlu authentication)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes (perlu authentication dengan Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('profile', [AuthController::class, 'profile']);
    });
    
    // Test route untuk memastikan authentication bekerja
    Route::get('user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user data',
            'data' => [
                'user' => $request->user()
            ]
        ]);
    });
});

// Health check route
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'PPOB API is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
