<?php
// File: routes/backpack/custom.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => 'web',
], function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('backpack.auth.login');
    Route::post('login', [LoginController::class, 'login'])->name('backpack.auth.login.post');
});

// --------------------------
// Custom Backpack Routes
// --------------------------
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => [
        'web',
        'auth:backpack',
        'check_if_admin',
    ],
    'namespace' => 'App\Http\Controllers\Admin',
], function () {
    // Dashboard route
    Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
    Route::get('/', 'AdminController@redirect')->name('backpack');
    
    // Wallet Management Routes
    Route::get('wallet', 'WalletManagementController@index')->name('admin.wallet.index');
    Route::post('wallet/adjust-balance', 'WalletManagementController@adjustBalance')->name('admin.wallet.adjust-balance');
    
    // ✅ TAMBAHKAN ROUTE REFERRAL STATS DI SINI
    Route::get('referral-stats', 'ReferralStatsController@index')->name('admin.referral-stats');
    
    // CRUD routes
    Route::crud('user', 'UserCrudController');
    Route::crud('product', 'ProductCrudController');
    Route::crud('transaction', 'TransactionCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('product-commission', 'ProductCommissionCrudController');
    Route::crud('banner', 'BannerCrudController');
    Route::crud('about-us', 'AboutUsCrudController');

    // Referral Transactions CRUD
    Route::crud('referral-transaction', 'ReferralTransactionCrudController');
});

/**
 * DO NOT ADD ANYTHING HERE.
 */