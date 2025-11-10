<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Banner extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_url',
        'is_active',
        'promo_code',
        'valid_until',
        'terms_conditions',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_valid', 'image_url_full'];

    /**
     * Scope untuk banner aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk banner yang masih berlaku
     */
    public function scopeValid($query)
    {
        return $query->where(function($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>', now());
        });
    }

    /**
     * Cek apakah promo masih berlaku
     */
    public function getIsValidAttribute()
    {
        if (!$this->valid_until) {
            return true;
        }
        
        return $this->valid_until->isFuture();
    }

    /**
     * Accessor untuk URL gambar lengkap
     */
    public function getImageUrlFullAttribute()
    {
        $value = $this->getRawOriginal('image_url');
        
        if (!$value) {
            return null;
        }

        // Jika sudah URL lengkap, return langsung
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Konversi path relatif ke URL lengkap
        return asset('storage/' . $value);
    }

    /**
     * Cek apakah file gambar ada di storage
     */
    public function imageExists()
    {
        $path = $this->getRawOriginal('image_url');
        return $path && Storage::disk('public')->exists($path);
    }

    /**
     * Get full physical path of image
     */
    public function getImagePhysicalPath()
    {
        $path = $this->getRawOriginal('image_url');
        return $path ? Storage::disk('public')->path($path) : null;
    }

    /**
     * Handle file upload otomatis
     */
    public function setImageUrlAttribute($value)
    {
        $attribute_name = "image_url";
        $disk = "public";
        $destination_path = "banners";

        // Jika value adalah string (sudah ada path), simpan langsung
        if (is_string($value)) {
            $this->attributes[$attribute_name] = $value;
            return;
        }

        // Jika value adalah file, upload
        if (is_object($value) && method_exists($value, 'isValid')) {
            $this->uploadFileToDisk($value, $attribute_name, $disk, $destination_path);
        } else {
            $this->attributes[$attribute_name] = null;
        }
    }

    /**
     * Format tanggal untuk display
     */
    public function getFormattedValidUntilAttribute()
    {
        return $this->valid_until ? $this->valid_until->format('d M Y H:i') : 'Tidak Terbatas';
    }
}