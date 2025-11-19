<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'about_us';

    protected $fillable = [
        'type',
        'title',
        'description',
        'link',
        'icon_path',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope untuk item aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk sorting berdasarkan order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Get type options untuk dropdown
     */
    public static function getTypeOptions()
    {
        return [
            'group_modipay' => 'Group Modipay',
            'whatsapp_admin' => 'WhatsApp Admin',
            'instagram' => 'Instagram',
        ];
    }

    /**
     * Get full icon URL
     */
    public function getIconUrlAttribute()
    {
        if (!$this->icon_path) {
            return null;
        }

        // Jika sudah URL lengkap, return langsung
        if (str_starts_with($this->icon_path, 'http://') || str_starts_with($this->icon_path, 'https://')) {
            return $this->icon_path;
        }

        // Konversi path relatif ke URL lengkap
        return asset('storage/' . $this->icon_path);
    }

    /**
     * Get formatted link (add protocol if needed)
     */
    public function getFormattedLinkAttribute()
    {
        if (!$this->link) {
            return null;
        }

        // Jika sudah ada protocol, return langsung
        if (str_starts_with($this->link, 'http://') || 
            str_starts_with($this->link, 'https://') ||
            str_starts_with($this->link, 'tel:') ||
            str_starts_with($this->link, 'mailto:')) {
            return $this->link;
        }

        // Tambah https:// jika belum ada
        return 'https://' . $this->link;
    }

    /**
     * Validation: only one entry per type
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Cek apakah sudah ada entry dengan type yang sama
            $exists = static::where('type', $model->type)
                ->where('id', '!=', $model->id ?? 0)
                ->exists();

            if ($exists) {
                throw new \Exception("Entry dengan type '{$model->type}' sudah ada. Silakan edit entry yang sudah ada.");
            }
        });
    }
}