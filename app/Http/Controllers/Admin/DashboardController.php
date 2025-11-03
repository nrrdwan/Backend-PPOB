<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $data = [];
        $data['title'] = 'Dashboard PPOB';
        $data['breadcrumbs'] = [
            'Dashboard' => backpack_url('dashboard'),
        ];
        
        $data['total_users'] = User::count();
        $data['active_users'] = User::where('created_at', '>=', now()->subDays(30))->count();
        $data['today_registrations'] = User::whereDate('created_at', today())->count();
        $data['this_month_registrations'] = User::whereMonth('created_at', now()->month)->count();
        
        $data['total_products'] = Product::count();
        $data['active_products'] = Product::active()->count();
        $data['total_transactions'] = Transaction::count();
        $data['today_transactions'] = Transaction::whereDate('created_at', today())->count();
        $data['success_transactions'] = Transaction::success()->count();
        $data['pending_transactions'] = Transaction::pending()->count();
        
        $data['total_revenue'] = Transaction::success()->sum('total_amount');
        $data['today_revenue'] = Transaction::success()->whereDate('created_at', today())->sum('total_amount');
        $data['this_month_revenue'] = Transaction::success()->whereMonth('created_at', now()->month)->sum('total_amount');
        
        $monthlyData = [];
        $monthlyLabels = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyLabels[] = $date->format('M Y');
            $monthlyData[] = Transaction::success()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }
        
        $data['monthly_stats'] = [
            'labels' => $monthlyLabels,
            'data' => $monthlyData
        ];
        
        $data['product_stats'] = [
            'pulsa' => Product::byType('pulsa')->active()->count(),
            'data' => Product::byType('data')->active()->count(),
            'pln' => Product::byType('pln')->active()->count(),
            'game' => Product::byType('game')->active()->count(),
            'emoney' => Product::byType('emoney')->active()->count(),
            'other' => Product::byType('other')->active()->count(),
        ];
        
        $data['recent_transactions'] = Transaction::with(['user', 'product'])
            ->latest()
            ->limit(5)
            ->get();
        
        return view('admin.dashboard', $data);
    }
}
