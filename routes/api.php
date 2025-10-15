<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PPOBController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\MidtransController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('pin', [AuthController::class, 'setPin']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::put('updateProfile', [AuthController::class, 'updateProfile']);
        Route::post('verify-pin', [AuthController::class, 'verifyPin']);
        Route::post('midtrans/create-bank-transfer', [MidtransController::class, 'createBankTransfer']);
        Route::post('midtrans/create-manual-transfer', [MidtransController::class, 'createManualTransfer']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications', [NotificationController::class, 'store']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    });

    Route::get('user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user data',
            'data'    => ['user' => $request->user()],
        ]);
    });

    Route::prefix('ppob')->group(function () {
        Route::get('categories', [PPOBController::class, 'getCategories']);
        Route::get('products', [PPOBController::class, 'getProducts']);
        Route::get('products/{productId}', [PPOBController::class, 'getProductDetail']);
        Route::post('purchase', [PPOBController::class, 'purchase']);
        Route::get('transaction/{transactionId}', [PPOBController::class, 'getTransactionStatus']);
        Route::get('transactions', [PPOBController::class, 'getTransactionHistory']);
    });

    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'getBalance']);
        Route::post('topup', [WalletController::class, 'topUp']);
        Route::get('history', [WalletController::class, 'getBalanceHistory']);
        Route::post('withdraw', [WalletController::class, 'withdraw']);
        Route::get('withdraw/{transaction_id?}', [WalletController::class, 'getWithdrawCode']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/', [NotificationController::class, 'store']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // 🔥 Midtrans Routes - SESUAI DENGAN IMPLEMENTASI KITA
    Route::prefix('midtrans')->group(function () {  
        // Core API Bank Transfer
        Route::post('/create-bank-transfer', [MidtransController::class, 'createBankTransfer']);
        Route::post('/create-manual-transfer', [MidtransController::class, 'createManualTransfer']);
        
        // Status & Details
        Route::get('/status/{transactionId}', [MidtransController::class, 'getStatus']);
        Route::get('/payment-details/{transactionId}', [MidtransController::class, 'getPaymentDetails']);
        
        // Manual & Notification
        Route::post('/manual-success', [MidtransController::class, 'manualSuccess']);
        Route::post('/notification', [MidtransController::class, 'notification']);
    });

    // 🔥 Opsional: Jika tetap butuh CoreMidtransController
    Route::prefix('midtrans/core')->group(function () {
        Route::post('transaction', [MidtransController::class, 'createTransaction']);
        Route::get('status/{orderId}', [MidtransController::class, 'getStatus']);
        Route::post('notification', [MidtransController::class, 'notification']);
    });

    // ✅ Simpan FCM Token dari Flutter
    Route::post('/save-fcm-token', function (Request $request) {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'FCM token disimpan']);
    });
});

// ✅ Public health check
Route::get('health', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'PPOB API is running',
        'timestamp' => now(),
        'version'   => '1.0.0',
    ]);
});