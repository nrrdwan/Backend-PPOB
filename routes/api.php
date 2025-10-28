<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    PPOBController,
    WalletController,
    MidtransController,
    NotificationController,
    BannerController
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Versi ini RESTful, efisien, dan tidak ada duplikasi endpoint.
| Struktur dibagi jelas antara:
| - Auth (publik dan private)
| - PPOB (produk & transaksi)
| - Wallet (saldo, topup, tarik)
| - Notifications (FCM & notifikasi user)
| - Midtrans (transaksi dan callback)
|--------------------------------------------------------------------------
*/

// ✅ Public Routes (tanpa autentikasi)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// ✅ Protected Routes (butuh token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('upload-profile-picture', [AuthController::class, 'uploadProfilePicture']);
        Route::post('pin', [AuthController::class, 'setPin']);
        Route::post('verify-pin', [AuthController::class, 'verifyPin']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('update-fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::post('save-fcm-token', [AuthController::class, 'saveFcmToken']);
    });

    Route::get('user', fn(Request $r) => response()->json([
        'success' => true,
        'message' => 'Authenticated user data',
        'data'    => ['user' => $r->user()],
    ]));

    Route::prefix('ppob')->group(function () {
        Route::get('categories', [PPOBController::class, 'getCategories']);
        Route::get('products', [PPOBController::class, 'getProducts']);
        Route::get('products/{id}', [PPOBController::class, 'getProductDetail']);
        Route::post('purchase', [PPOBController::class, 'purchase']);
        Route::get('transactions', [PPOBController::class, 'getTransactionHistory']);
        Route::get('transaction/{id}', [PPOBController::class, 'getTransactionStatus']);
    });

    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'getBalance']);
        Route::get('history', [WalletController::class, 'getBalanceHistory']);
        Route::post('topup', [WalletController::class, 'topUp']);
        Route::post('withdraw', [WalletController::class, 'withdraw']);
        Route::get('withdraw/{transaction_id?}', [WalletController::class, 'getWithdrawCode']);
        Route::post('confirm-withdraw', [WalletController::class, 'confirmWithdraw']);
        Route::post('generate-withdraw-code', [WalletController::class, 'generateWithdrawCode']);
        Route::post('qr/generate', [WalletController::class, 'generateQrCode']);
        Route::get('qr/lookup', [WalletController::class, 'lookupQr']);
        Route::post('qr/transfer', [WalletController::class, 'transferViaQr']);
    });

    Route::apiResource('notifications', NotificationController::class)
        ->only(['index', 'store', 'destroy']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    Route::prefix('midtrans')->group(function () {
        Route::post('create-bank-transfer', [MidtransController::class, 'createBankTransfer']);
        Route::post('create-manual-transfer', [MidtransController::class, 'createManualTransfer']);
        Route::get('status/{id}', [MidtransController::class, 'getStatus']);
        Route::get('payment-details/{id}', [MidtransController::class, 'getPaymentDetails']);
        Route::post('manual-success', [MidtransController::class, 'manualSuccess']);
    });
});

Route::get('banners', [BannerController::class, 'index']);

Route::post('midtrans/notification', [MidtransController::class, 'notification']);

Route::get('health', fn() => response()->json([
    'success'   => true,
    'message'   => 'PPOB API is running',
    'timestamp' => now(),
    'version'   => '1.0.0',
]));