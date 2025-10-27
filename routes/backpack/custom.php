<?php

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
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => [
        'web',
        'auth:backpack',
        'check_if_admin', // pastikan middleware ini sudah terdaftar di Kernel
    ],
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    // Dashboard route
    Route::get('dashboard', 'AdminController@dashboard')->name('backpack.dashboard');
    Route::get('/', 'AdminController@redirect')->name('backpack');
    
    // Wallet Management Routes
    Route::get('wallet', 'WalletManagementController@index')->name('admin.wallet.index');
    Route::post('wallet/adjust-balance', 'WalletManagementController@adjustBalance')->name('admin.wallet.adjust-balance');
    
    // CRUD routes
    Route::crud('user', 'UserCrudController');
    Route::crud('product', 'ProductCrudController');
    Route::crud('transaction', 'TransactionCrudController');
    Route::crud('role', 'RoleCrudController');
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('product-commission', 'ProductCommissionCrudController');
    Route::crud('banner', 'BannerCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
