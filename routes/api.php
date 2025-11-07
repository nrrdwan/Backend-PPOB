<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    PPOBController,
    WalletController,
    MidtransController,
    NotificationController,
    BannerController,
    TransactionHistoryController,
    SavedContactController,
    WhatsappController,
    DeviceSessionController
};

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

// ✅ Public Routes (tidak butuh token)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('forgot-pin', [AuthController::class, 'forgotPin']);
    Route::post('reset-pin', [AuthController::class, 'resetPin']);
    Route::post('check-email', [AuthController::class, 'checkEmail']);
});

// ✅ Banner Routes (Public - bisa diakses tanpa login)
Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::get('/{id}', [BannerController::class, 'show']);
});

// ✅ Midtrans Notification (Public - untuk webhook)
Route::post('midtrans/notification', [MidtransController::class, 'notification']);

// ✅ Health Check (Public)
Route::get('health', function () {
    return response()->json([
        'success'   => true,
        'message'   => 'PPOB API is running',
        'timestamp' => now(),
        'version'   => '1.0.0',
    ]);
});

// ✅ Protected Routes (butuh token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // ==================== AUTH & PROFILE ROUTES ====================
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
        Route::post('change-pin', [AuthController::class, 'changePin']);
        
        // Device Sessions
        Route::get('devices', [DeviceSessionController::class, 'index']);
        Route::post('devices/terminate-all', [DeviceSessionController::class, 'destroyAll']);
        Route::delete('devices/{id}', [DeviceSessionController::class, 'destroy']);
    });

    // ==================== WHATSAPP ROUTES ====================
    Route::prefix('whatsapp')->group(function () {
        Route::get('/group-link', [WhatsappController::class, 'getGroupLink']);
        Route::get('/admin-contact', [WhatsappController::class, 'getAdminContact']);
    });

    // ==================== CONTACTS ROUTES ====================
    Route::prefix('contacts')->group(function () {
        Route::get('/', [SavedContactController::class, 'index']);
        Route::post('/', [SavedContactController::class, 'store']);
        Route::delete('/{id}', [SavedContactController::class, 'destroy']);
    });

    // ==================== USER ROUTES ====================
    Route::get('user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user data',
            'data'    => ['user' => $request->user()],
        ]);
    });

    // ==================== TRANSACTIONS ROUTES ====================
    Route::get('transactions', [TransactionHistoryController::class, 'index']);
    Route::post('transactions', [TransactionHistoryController::class, 'store']);

    // ==================== PPOB ROUTES ====================
    Route::prefix('ppob')->group(function () {
        Route::get('categories', [PPOBController::class, 'getCategories']);
        Route::get('products', [PPOBController::class, 'getProducts']);
        Route::get('products/{id}', [PPOBController::class, 'getProductDetail']);
        Route::post('purchase', [PPOBController::class, 'purchase']);
        Route::get('transactions', [PPOBController::class, 'getTransactionHistory']);
        Route::get('transaction/{id}', [PPOBController::class, 'getTransactionStatus']);
    });

    // ==================== WALLET ROUTES ====================
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
        Route::post('deduct-balance', [WalletController::class, 'deductBalance']);
    });

    // ==================== NOTIFICATIONS ROUTES ====================
    Route::apiResource('notifications', NotificationController::class)
        ->only(['index', 'store', 'destroy']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // ==================== MIDTRANS ROUTES ====================
    Route::prefix('midtrans')->group(function () {
        Route::post('create-bank-transfer', [MidtransController::class, 'createBankTransfer']);
        Route::post('create-manual-transfer', [MidtransController::class, 'createManualTransfer']);
        Route::post('create-qris', [MidtransController::class, 'createQRISPayment']);
        Route::post('create-ewallet', [MidtransController::class, 'createEwalletPayment']);
        Route::get('status/{id}', [MidtransController::class, 'getStatus']);
        Route::get('payment-details/{id}', [MidtransController::class, 'getPaymentDetails']);
        Route::post('payment-details-from-qr', [MidtransController::class, 'getPaymentDetailsFromQR']);
        Route::post('qris/simulate-payment', [MidtransController::class, 'simulateQRISPayment']);
        Route::post('manual-success', [MidtransController::class, 'manualSuccess']);
    });

    // ==================== BANNER PROTECTED ROUTES ====================
    Route::prefix('banners')->group(function () {
        Route::post('/', [BannerController::class, 'store']);
        Route::put('/{id}', [BannerController::class, 'update']);
        Route::patch('/{id}', [BannerController::class, 'update']);
        Route::delete('/{id}', [BannerController::class, 'destroy']);
        
        // ✅ Optional: Route untuk admin management
        Route::get('/admin/all', [BannerController::class, 'getAllBanners']); // Untuk admin lihat semua banner
        Route::patch('/{id}/toggle-status', [BannerController::class, 'toggleStatus']); // Untuk toggle status aktif/tidak
    });
});