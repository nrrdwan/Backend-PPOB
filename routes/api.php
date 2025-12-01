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
    DeviceSessionController,
    ProductController,
    AboutUsController
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
    
    // ✅ Verify referral code (public - untuk validasi saat register)
    Route::post('verify-referral-code', [AuthController::class, 'verifyReferralCode']);
});

// ✅ Banner Routes (Public - bisa diakses tanpa login)
Route::prefix('banners')->group(function () {
    Route::get('/', [BannerController::class, 'index']);
    Route::get('/{id}', [BannerController::class, 'show']);
});

// ✅ Product Routes (Public - bisa diakses tanpa login)
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::get('/type/{type}', [ProductController::class, 'getByType']);
    Route::get('/provider/{provider}', [ProductController::class, 'getByProvider']);
});

// ✅ About Us Routes (Public - bisa diakses tanpa login)
Route::prefix('about-us')->group(function () {
    Route::get('/', [AboutUsController::class, 'index']);
    Route::get('/{type}', [AboutUsController::class, 'getByType']);
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

// ✅ Test Banner Endpoint (Public)
Route::get('test-banners', function () {
    try {
        $banners = \App\Models\Banner::active()
            ->where(function($query) {
                $query->whereNull('valid_until')
                      ->orWhere('valid_until', '>', now());
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Test banner endpoint',
            'banners_count' => $banners->count(),
            'banners' => $banners->map(function($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'is_active' => $banner->is_active,
                    'valid_until' => $banner->valid_until,
                    'image_url' => $banner->image_url,
                    'image_url_full' => $banner->image_url_full,
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// ✅ Test Product Endpoint (Public)
Route::get('test-products', function () {
    try {
        $products = \App\Models\Product::where('is_active', true)
            ->where('type', 'pln')
            ->orderBy('selling_price', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Test product endpoint',
            'products_count' => $products->count(),
            'products' => $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'type' => $product->type,
                    'price' => $product->price,
                    'selling_price' => $product->selling_price,
                    'is_active' => $product->is_active,
                    'is_available' => $product->isAvailable(),
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// ✅ Test About Us Endpoint (Public)
Route::get('test-about-us', function () {
    try {
        $items = \App\Models\AboutUs::active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Test About Us endpoint',
            'items_count' => $items->count(),
            'items' => $items->map(function($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'link' => $item->link,
                    'formatted_link' => $item->formatted_link,
                    'icon_url' => $item->icon_url,
                    'is_active' => $item->is_active,
                ];
            })
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
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
        
        // ✅ Referral Stats (protected - butuh login)
        Route::get('referral-stats', [AuthController::class, 'getReferralStats']);
        
        // Device Sessions
        Route::get('devices', [DeviceSessionController::class, 'index']);
        Route::post('devices/terminate-all', [DeviceSessionController::class, 'destroyAll']);
        Route::delete('devices/{id}', [DeviceSessionController::class, 'destroy']);
    });

    // ==================== PRODUCT PROTECTED ROUTES ====================
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
        
        // ✅ Optional: Route untuk admin management
        Route::get('/admin/all', [ProductController::class, 'getAllProducts']); // Untuk admin lihat semua produk
        Route::patch('/{id}/toggle-status', [ProductController::class, 'toggleStatus']); // Untuk toggle status aktif/tidak
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
        Route::put('/contacts/{id}', [SavedContactController::class, 'update']);
        Route::delete('/{id}', [SavedContactController::class, 'destroy']);
        Route::post('/contacts/{id}/toggle-favorite', [SavedContactController::class, 'toggleFavorite']);
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

    // ==================== ABOUT US PROTECTED ROUTES ====================
    Route::prefix('about-us')->group(function () {
        Route::post('/', [AboutUsController::class, 'store']);
        Route::put('/{id}', [AboutUsController::class, 'update']);
        Route::patch('/{id}', [AboutUsController::class, 'update']);
        Route::delete('/{id}', [AboutUsController::class, 'destroy']);
        
        // ✅ Optional: Route untuk admin management
        Route::get('/admin/all', [AboutUsController::class, 'getAllItems']); // Untuk admin lihat semua item
        Route::patch('/{id}/toggle-status', [AboutUsController::class, 'toggleStatus']); // Untuk toggle status aktif/tidak
    });
});