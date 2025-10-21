<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Auth Routes
// --------------------------
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => 'web',
    'namespace' => 'App\Http\Controllers\Auth',
], function () {
    // Override default login route with custom controller
    Route::get('login', 'LoginController@showLoginForm')->name('backpack.auth.login');
    Route::post('login', 'LoginController@login');
});

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
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
