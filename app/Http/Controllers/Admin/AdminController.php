<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;

class AdminController extends Controller
{
    public function dashboard()
    {
        $data = [];
        $data['title'] = 'Dashboard PPOB';
        $data['breadcrumbs'] = [
            'Dashboard' => backpack_url('dashboard'),
        ];
        
        // Statistik untuk dashboard PPOB
        $data['total_users'] = User::count();
        $data['active_users'] = User::where('created_at', '>=', now()->subDays(30))->count();
        $data['today_transactions'] = 0; // Transaction::whereDate('created_at', today())->count();
        $data['this_month_revenue'] = 0; // Transaction::whereMonth('created_at', now()->month)->where('status', 'success')->sum('amount');
        
        // Product stats
        $data['active_products'] = Product::where('is_active', true)->count();
        $data['total_transactions'] = 0; // Transaction::count();
        $data['total_revenue'] = 0; // Transaction::where('status', 'success')->sum('amount');
        $data['pending_transactions'] = 0; // Transaction::where('status', 'pending')->count();
        $data['success_transactions'] = 0; // Transaction::where('status', 'success')->count();
        
        // Recent transactions
        $data['recent_transactions'] = collect(); // Transaction::with('user', 'product')->latest()->limit(5)->get();
        
        // Product stats by type
        $data['product_stats'] = [
            'pulsa' => Product::where('type', 'pulsa')->count(),
            'data' => Product::where('type', 'data')->count(),
            'pln' => Product::where('type', 'pln')->count(),
            'game' => Product::where('type', 'game')->count(),
            'emoney' => Product::where('type', 'emoney')->count(),
            'other' => Product::where('type', 'other')->count(),
        ];
        
        // Data untuk chart (contoh data, nanti bisa disesuaikan dengan model transaksi)
        $data['monthly_stats'] = [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [65, 59, 80, 81, 56, 55]
        ];
        
        return view('admin.dashboard', $data);
    }

    public function redirect()
    {
        return redirect(backpack_url('dashboard'));
    }
}
