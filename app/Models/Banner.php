<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
     * Handle file upload otomatis ke storage/public/banners
     */
    public function setImageUrlAttribute($value)
    {
        $attribute_name = "image_url";
        $disk = "public";
        $destination_path = "banners";

        \Log::info('🔄 Mulai upload file banner:', [
            'value_type' => gettype($value),
            'is_file' => is_object($value) && method_exists($value, 'isValid'),
            'disk' => $disk,
            'destination' => $destination_path
        ]);

        if (is_object($value) && method_exists($value, 'isValid')) {
            // Upload file baru
            $this->uploadFileToDisk($value, $attribute_name, $disk, $destination_path);
            
            \Log::info('✅ File banner berhasil diupload:', [
                'saved_path' => $this->attributes[$attribute_name],
                'full_disk_path' => Storage::disk($disk)->path($this->attributes[$attribute_name])
            ]);
        } else {
            $this->attributes[$attribute_name] = $value;
        }
    }

    /**
     * Accessor untuk URL gambar lengkap - UNTUK API/FLUTTER
     */
    public function getImageUrlFullAttribute()
    {
        // Gunakan parent::getRawOriginal() yang sudah ada
        $value = parent::getRawOriginal('image_url');
        
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
     * Accessor original untuk Backpack compatibility
     */
    public function getImageUrlAttribute($value)
    {
        // Untuk Backpack, return value asli
        return $value;
    }

    /**
     * Scope untuk banner aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Cek apakah file gambar ada di storage
     */
    public function imageExists()
    {
        $path = parent::getRawOriginal('image_url');
        return $path && Storage::disk('public')->exists($path);
    }

    /**
     * Get full physical path of image
     */
    public function getImagePhysicalPath()
    {
        $path = parent::getRawOriginal('image_url');
        return $path ? Storage::disk('public')->path($path) : null;
    }
}