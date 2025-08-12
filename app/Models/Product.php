<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use CrudTrait;
    protected $fillable = [
        'code',
        'name',
        'provider',
        'type',
        'price',
        'admin_fee',
        'selling_price',
        'description',
        'is_active',
        'stock',
        'is_unlimited',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_unlimited' => 'boolean',
        'settings' => 'array',
        'price' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'selling_price' => 'decimal:2'
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope untuk status
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    // Methods untuk business logic
    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->is_unlimited && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    public function reduceStock($quantity = 1)
    {
        if (!$this->is_unlimited) {
            $this->decrement('stock', $quantity);
        }
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0, ',', '.');
    }

    public function getFormattedSellingPriceAttribute()
    {
        return number_format($this->selling_price, 0, ',', '.');
    }
}
