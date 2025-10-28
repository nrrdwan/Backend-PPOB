<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        ];
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
        // Check if user is active
        if (!$this->is_active) {
            return false;
        }

        // Check if user has admin or operator role (backward compatibility)
        if (in_array($this->role, ['admin', 'operator'])) {
            return true;
        }

        // Check if user has roles with admin access permission
        return $this->hasPermission('access-admin-panel');
    }

    // Wallet/Balance methods
    public function hasBalance($amount)
    {
        return $this->balance >= $amount;
    }

    public function addBalance($amount)
    {
        $this->increment('balance', $amount);
        return $this->fresh();
    }

    public function deductBalance($amount)
    {
        if (!$this->hasBalance($amount)) {
            return false;
        }
        
        $this->decrement('balance', $amount);
        return $this->fresh();
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 0, ',', '.');
    }
}
