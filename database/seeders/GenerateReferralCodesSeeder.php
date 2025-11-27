<?php
// File: database/seeders/GenerateReferralCodesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateReferralCodesSeeder extends Seeder
{
    /**
     * Generate referral codes untuk user yang belum punya
     * 
     * Usage: php artisan db:seed --class=GenerateReferralCodesSeeder
     */
    public function run(): void
    {
        echo "\n🔄 Generating referral codes for existing users...\n\n";

        $usersWithoutCode = User::whereNull('referral_code')
            ->orWhere('referral_code', '')
            ->get();

        if ($usersWithoutCode->isEmpty()) {
            echo "✅ All users already have referral codes!\n";
            echo "Total users: " . User::count() . "\n";
            return;
        }

        echo "📊 Found {$usersWithoutCode->count()} users without referral codes\n\n";

        $updated = 0;
        foreach ($usersWithoutCode as $user) {
            $code = $this->generateUniqueReferralCode();
            $user->referral_code = $code;
            $user->save();

            echo "✅ User #{$user->id} ({$user->email}): {$code}\n";
            $updated++;
        }

        echo "\n════════════════════════════════════════\n";
        echo "   REFERRAL CODES GENERATED\n";
        echo "════════════════════════════════════════\n";
        echo "Total users updated: {$updated}\n";
        echo "Total users in DB: " . User::count() . "\n";
        echo "════════════════════════════════════════\n\n";
    }

    /**
     * Generate unique 6-character referral code
     */
    private function generateUniqueReferralCode(): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            $attempts++;
            
            // Generate 6 karakter alfanumerik (huruf besar + angka)
            $code = strtoupper(Str::random(6));
            
            // Pastikan ada kombinasi huruf dan angka
            if (preg_match('/[A-Z]/', $code) && preg_match('/[0-9]/', $code)) {
                // Cek apakah kode sudah ada di database
                $exists = User::where('referral_code', $code)->exists();
                
                if (!$exists) {
                    return $code;
                }
            }

            if ($attempts >= $maxAttempts) {
                // Fallback: gunakan timestamp
                $code = strtoupper(substr(md5(microtime()), 0, 6));
                if (!User::where('referral_code', $code)->exists()) {
                    return $code;
                }
            }
        } while ($attempts < $maxAttempts);

        throw new \Exception("Failed to generate unique referral code after {$maxAttempts} attempts");
    }
}