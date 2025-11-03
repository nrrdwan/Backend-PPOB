<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletManagementController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('role', '!=', 'Administrator')->count();
        $totalBalance = User::where('role', '!=', 'Administrator')->sum('balance');
        $totalTopUp = Transaction::where('type', 'topup')
                               ->where('status', 'success')
                               ->sum('total_amount');
        $totalSpending = Transaction::whereIn('type', ['pulsa', 'pln', 'pdam', 'game', 'emoney', 'other'])
                                  ->where('status', 'success')
                                  ->sum('total_amount');
        $recentTransactions = Transaction::with(['user', 'product'])
                                       ->orderBy('created_at', 'desc')
                                       ->limit(10)
                                       ->get();

        $topBalanceUsers = User::where('role', '!=', 'Administrator')
                              ->where('balance', '>', 0)
                              ->orderBy('balance', 'desc')
                              ->limit(10)
                              ->get();
        
        $monthlyStats = Transaction::select(
                DB::raw('TO_CHAR(created_at, \'YYYY-MM\') as month'),
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();
        
        return view('admin.wallet.index', compact(
            'totalUsers', 
            'totalBalance', 
            'totalTopUp', 
            'totalSpending',
            'recentTransactions',
            'topBalanceUsers',
            'monthlyStats'
        ));
    }
    
    public function adjustBalance(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric',
            'type' => 'required|in:add,subtract',
            'notes' => 'required|string|max:500'
        ]);
        
        $user = User::find($request->user_id);
        $amount = abs($request->amount);
        
        DB::beginTransaction();
        
        try {
            if ($request->type === 'add') {
                $user->addBalance($amount);
                $transactionType = 'topup';
                $notes = 'Admin adjustment: ' . $request->notes;
            } else {
                if (!$user->hasBalance($amount)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Saldo user tidak mencukupi'
                    ], 400);
                }
                $user->deductBalance($amount);
                $transactionType = 'adjustment';
                $notes = 'Admin deduction: ' . $request->notes;
                $amount = -$amount;
            }
            
            Transaction::create([
                'user_id' => $user->id,
                'product_id' => null,
                'phone_number' => null,
                'customer_id' => null,
                'amount' => $amount,
                'admin_fee' => 0,
                'total_amount' => $amount,
                'status' => 'success',
                'type' => $transactionType,
                'notes' => $notes,
                'processed_at' => now()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Saldo berhasil disesuaikan',
                'new_balance' => $user->balance
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyesuaikan saldo: ' . $e->getMessage()
            ], 500);
        }
    }
}
