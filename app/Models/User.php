<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use CrudTrait;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'full_name',
        'email',
        'phone',
        'password',
        'pin',
        'balance',
        'profile_picture',
        'role',
        'is_active',
        'last_login_at',
        'referral_code',
        'referred_by',
        'referral_count',
        'referral_earnings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'balance' => 'decimal:2',
            'referral_count' => 'integer',
            'referral_earnings' => 'decimal:2',
        ];
    }

    /**
     * Boot method untuk auto-generate referral code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = self::generateUniqueReferralCode();
            }
        });
    }

    /**
     * Generate unique 6-character referral code
     */
    public static function generateUniqueReferralCode(): string
    {
        do {
            // Generate 6 karakter alfanumerik (huruf besar + angka)
            $code = strtoupper(Str::random(6));
            
            // Pastikan ada kombinasi huruf dan angka
            if (preg_match('/[A-Z]/', $code) && preg_match('/[0-9]/', $code)) {
                // Cek apakah kode sudah ada di database
                $exists = self::where('referral_code', $code)->exists();
                
                if (!$exists) {
                    return $code;
                }
            }
        } while (true);
    }

    /**
     * Get users who used this user's referral code
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'referral_code');
    }

    /**
     * Get the user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by', 'referral_code');
    }

    /**
     * Get all referral transactions as referrer
     */
    public function referralTransactions()
    {
        return $this->hasMany(ReferralTransaction::class, 'referrer_id');
    }

    /**
     * Get formatted referral earnings
     */
    public function getFormattedReferralEarningsAttribute()
    {
        return 'Rp ' . number_format($this->referral_earnings, 0, ',', '.');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return strtolower($this->role) === 'admin';
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return strtolower($this->role) === 'operator';
    }

    /**
     * Get user's transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * User belongs to many roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('slug', $role)->exists();
        }

        return $this->roles()->where('id', $role->id)->exists();
    }

    /**
     * Check if user has permission
     */
    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign role to user
     */
    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        }

        if ($role) {
            $this->roles()->syncWithoutDetaching([$role->id]);
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Check if user can access admin panel (improved version)
     */
    public function canAccessAdminPanel(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if (in_array($this->role, ['admin', 'operator'])) {
            return true;
        }
        return $this->hasPermission('access-admin-panel');
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasBalance($amount)
    {
        return $this->balance >= $amount;
    }

    /**
     * Add balance to user
     */
    public function addBalance($amount)
    {
        $this->increment('balance', $amount);
        return $this->fresh();
    }

    /**
     * Deduct balance from user
     */
    public function deductBalance($amount)
    {
        if (!$this->hasBalance($amount)) {
            return false;
        }
        
        $this->decrement('balance', $amount);
        return $this->fresh();
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', '.');
    }

    /**
     * Add referral earnings
     */
    public function addReferralEarnings($amount)
    {
        $this->increment('referral_earnings', $amount);
        return $this->fresh();
    }

    /**
     * Increment referral count
     */
    public function incrementReferralCount()
    {
        $this->increment('referral_count');
        return $this->fresh();
    }
}