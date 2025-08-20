<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Otomatis generate slug dari nama
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($permission) {
            if (empty($permission->slug)) {
                $permission->slug = Str::slug($permission->name);
            }
        });

        static::updating(function ($permission) {
            if ($permission->isDirty('name') && empty($permission->slug)) {
                $permission->slug = Str::slug($permission->name);
            }
        });
    }

    /**
     * Permission dimiliki oleh banyak role
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Scope berdasarkan grup
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Dapatkan grup permission
     */
    public static function getGroups()
    {
        return self::distinct('group')->pluck('group')->toArray();
    }
}
