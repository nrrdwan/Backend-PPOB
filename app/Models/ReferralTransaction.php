<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait; // ✅ TAMBAHKAN INI
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralTransaction extends Model
{
    use HasFactory;
    use CrudTrait; // ✅ TAMBAHKAN INI

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'referral_number',
        'commission_amount',
        'status',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'referral_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who referred (pemilik kode referral)
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred (yang daftar pakai kode)
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Calculate commission based on referral number
     */
    public static function calculateCommission(int $referralNumber): float
    {
        // Komisi bertahap:
        // User 1: 10000, User 2: 20000, User 3: 30000, User 4: 40000, User 5: 50000
        // User 6: 60000, User 7: 70000, User 8: 80000, User 9: 90000, User 10: 100000
        // User 11+: 20000 (fixed)
        
        if ($referralNumber <= 5) {
            return $referralNumber * 10000;
        } elseif ($referralNumber <= 10) {
            return ($referralNumber - 5) * 10000 + 50000;
        } else {
            return 20000;
        }
    }

    /**
     * Get formatted commission amount
     */
    public function getFormattedCommissionAttribute()
    {
        return 'Rp ' . number_format($this->commission_amount, 0, ',', '.');
    }
}