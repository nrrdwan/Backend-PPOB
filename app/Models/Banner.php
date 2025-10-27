<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use CrudTrait;
    use HasFactory;

    protected $fillable = [
        'title',
        'image_url',
        'is_active',
    ];

    /**
     * Handle file upload otomatis ke storage/public/banners
     */
    public function setImageUrlAttribute($value)
    {
        $attribute_name = "image_url";
        $disk = "public";
        $destination_path = "banners";

        // Jika value adalah UploadedFile, upload
        if (is_object($value) && method_exists($value, 'isValid')) {
            $this->uploadFileToDisk($value, $attribute_name, $disk, $destination_path);
        } else {
            // Jika string biasa, simpan langsung
            $this->attributes[$attribute_name] = $value;
        }
    }
}