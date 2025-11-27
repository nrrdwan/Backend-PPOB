<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ReferralTransaction;
use Illuminate\Support\Facades\Hash;

class ReferralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Usage: php artisan db:seed --class=ReferralSeeder
     */
    public function run(): void
    {
        // Create main user with referral code
        $mainUser = User::firstOrCreate(
            ['email' => 'referrer@test.com'],
            [
                'name' => 'Main Referrer',
                'full_name' => 'Main Referrer User',
                'email' => 'referrer@test.com',
                'password' => Hash::make('password123'),
                'pin' => Hash::make('123456'),
                'phone' => '081234567890',
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
                'balance' => 0,
                'referral_count' => 0,
                'referral_earnings' => 0,
            ]
        );

        echo "✅ Main user created: {$mainUser->email}\n";
        echo "📱 Referral Code: {$mainUser->referral_code}\n\n";

        // Create 12 referred users
        for ($i = 1; $i <= 12; $i++) {
            $referredUser = User::firstOrCreate(
                ['email' => "referred{$i}@test.com"],
                [
                    'name' => "Referred User {$i}",
                    'full_name' => "Referred User {$i}",
                    'email' => "referred{$i}@test.com",
                    'password' => Hash::make('password123'),
                    'pin' => Hash::make('123456'),
                    'phone' => '08123456789' . $i,
                    'role' => 'user',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'balance' => 0,
                    'referred_by' => $mainUser->referral_code,
                ]
            );

            // Update referral count
            $mainUser->increment('referral_count');
            $currentCount = $mainUser->referral_count;

            // Calculate commission
            $commission = ReferralTransaction::calculateCommission($currentCount);

            // Add commission to balance
            $mainUser->increment('balance', $commission);
            $mainUser->increment('referral_earnings', $commission);

            // Create transaction record
            ReferralTransaction::create([
                'referrer_id' => $mainUser->id,
                'referred_id' => $referredUser->id,
                'referral_code' => $mainUser->referral_code,
                'referral_number' => $currentCount,
                'commission_amount' => $commission,
                'status' => 'paid',
            ]);

            echo "✅ Referred User {$i}: {$referredUser->email} | Commission: Rp " . number_format($commission, 0, ',', '.') . "\n";
        }

        echo "\n";
        echo "════════════════════════════════════════\n";
        echo "   REFERRAL SEEDER COMPLETED\n";
        echo "════════════════════════════════════════\n";
        echo "Main User Email: referrer@test.com\n";
        echo "Password: password123\n";
        echo "PIN: 123456\n";
        echo "Referral Code: {$mainUser->referral_code}\n";
        echo "Total Referrals: {$mainUser->referral_count}\n";
        echo "Total Earnings: Rp " . number_format($mainUser->referral_earnings, 0, ',', '.') . "\n";
        echo "════════════════════════════════════════\n";
    }
}