<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ReferralTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralStatsController extends Controller
{
    /**
     * Display referral statistics dashboard
     */
    public function index()
    {
        // Overall Stats
        $totalReferrals = User::whereNotNull('referred_by')->count();
        $totalCommissionPaid = ReferralTransaction::where('status', 'paid')->sum('commission_amount');
        $activeReferrers = User::where('referral_count', '>', 0)->count();
        $avgReferralsPerUser = User::where('referral_count', '>', 0)->avg('referral_count');

        // Top Referrers
        $topReferrers = User::select('id', 'name', 'email', 'referral_code', 'referral_count', 'referral_earnings')
            ->where('referral_count', '>', 0)
            ->orderBy('referral_earnings', 'desc')
            ->limit(10)
            ->get();

        // Recent Transactions
        $recentTransactions = ReferralTransaction::with(['referrer', 'referred'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // ✅ Monthly Stats - FIXED untuk PostgreSQL
        $monthlyStats = ReferralTransaction::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total_referrals'),
                DB::raw('SUM(commission_amount) as total_commission')
            )
            ->where('status', 'paid')
            ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        // Commission Distribution
        $commissionDistribution = [
            '10k' => ReferralTransaction::where('commission_amount', 10000)->count(),
            '20k' => ReferralTransaction::where('commission_amount', 20000)->count(),
            '30k' => ReferralTransaction::where('commission_amount', 30000)->count(),
            '40k' => ReferralTransaction::where('commission_amount', 40000)->count(),
            '50k' => ReferralTransaction::where('commission_amount', 50000)->count(),
            '60k' => ReferralTransaction::where('commission_amount', 60000)->count(),
            '70k' => ReferralTransaction::where('commission_amount', 70000)->count(),
            '80k' => ReferralTransaction::where('commission_amount', 80000)->count(),
            '90k' => ReferralTransaction::where('commission_amount', 90000)->count(),
            '100k' => ReferralTransaction::where('commission_amount', 100000)->count(),
        ];

        return view('admin.referral_stats', compact(
            'totalReferrals',
            'totalCommissionPaid',
            'activeReferrers',
            'avgReferralsPerUser',
            'topReferrers',
            'recentTransactions',
            'monthlyStats',
            'commissionDistribution'
        ));
    }
}