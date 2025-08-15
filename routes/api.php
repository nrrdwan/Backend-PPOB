<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\PPOBController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Semua route di sini otomatis berada pada prefix "api" (RouteServiceProvider).
| Komentar diseragamkan, penataan dikelompokkan, dan chaining dipakai agar rapi.
*/

/**
 * --------------------------------------------------------------------------
 * Public: Auth (tanpa autentikasi)
 * --------------------------------------------------------------------------
 */
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

/**
 * --------------------------------------------------------------------------
 * Protected: Semua route yang butuh Sanctum
 * --------------------------------------------------------------------------
 */
Route::middleware('auth:sanctum')->group(function () {
    // Auth (protected)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('profile', [AuthController::class, 'profile']);
    });

    // Sanity check user (protected)
    Route::get('user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user data',
            'data'    => ['user' => $request->user()],
        ]);
    });

    // PPOB (protected)
    Route::prefix('ppob')->group(function () {
        // Product
        Route::get('categories', [PPOBController::class, 'getCategories']);
        Route::get('products', [PPOBController::class, 'getProducts']);
        Route::get('products/{productId}', [PPOBController::class, 'getProductDetail']);

        // Transaction
        Route::post('purchase', [PPOBController::class, 'purchase']);
        Route::get('transaction/{transactionId}', [PPOBController::class, 'getTransactionStatus']);
        Route::get('transactions', [PPOBController::class, 'getTransactionHistory']);
    });

    // Wallet (protected)
    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'getBalance']);
        Route::post('topup', [WalletController::class, 'topUp']);
        Route::get('history', [WalletController::class, 'getBalanceHistory']);
    });

    // Midtrans (protected)
    Route::prefix('midtrans')->group(function () {
        Route::post('create-token', [MidtransController::class, 'createSnapToken']);
        Route::get('status/{transactionId}', [MidtransController::class, 'getStatus']);
        Route::post('generate-signature', [MidtransController::class, 'generateSignature']);
    });
});

/**
 * --------------------------------------------------------------------------
 * Public: Midtrans callback & helper (tanpa autentikasi)
 * --------------------------------------------------------------------------
 */
Route::prefix('midtrans')->group(function () {
    Route::post('notification', [MidtransController::class, 'notification']);
    Route::post('debug-notification', [MidtransController::class, 'debugNotification']);
    Route::post('test-generate-signature', [MidtransController::class, 'generateSignature']);

    // Halaman redirect (opsional untuk front-end)
    Route::get('finish', fn () => view('midtrans.finish'));
    Route::get('error', fn () => view('midtrans.error'));
    Route::get('pending', fn () => view('midtrans.pending'));
});

/**
 * --------------------------------------------------------------------------
 * Public: Health check
 * --------------------------------------------------------------------------
 */
Route::get('health', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'PPOB API is running',
        'timestamp' => now(),
        'version'   => '1.0.0',
    ]);
});
