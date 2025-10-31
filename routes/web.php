<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Route for access denied page
Route::get('/access-denied', function () {
    $userRole = request()->query('role', 'Unknown');
    return view('auth.access-denied', compact('userRole'));
})->name('access.denied');

// ✅ Redirect ke Backpack login
Route::get('/login', function () {
    return redirect(backpack_url('login'));
})->name('login');