<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PPOBController;
use App\Http\Controllers\Api\WalletController;

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

    // PPOB routes
    Route::prefix('ppob')->group(function () {
        // Product management
        Route::get('categories', [PPOBController::class, 'getCategories']);
        Route::get('products', [PPOBController::class, 'getProducts']);
        Route::get('products/{productId}', [PPOBController::class, 'getProductDetail']);
        
        // Transaction management
        Route::post('purchase', [PPOBController::class, 'purchase']);
        Route::get('transaction/{transactionId}', [PPOBController::class, 'getTransactionStatus']);
        Route::get('transactions', [PPOBController::class, 'getTransactionHistory']);
    });

    // Wallet routes
    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'getBalance']);
        Route::post('topup', [WalletController::class, 'topUp']);
        Route::get('history', [WalletController::class, 'getBalanceHistory']);
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
