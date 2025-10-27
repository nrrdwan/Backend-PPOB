<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProfilePicture extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_url',
    ];

    protected static function boot()
    {
        parent::boot();

        // Hapus file fisik saat record dihapus
        static::deleting(function ($profilePicture) {
            if ($profilePicture->image_url) {
                $path = str_replace(asset('storage/'), '', $profilePicture->image_url);
                Storage::disk('public')->delete($path);
            }
        });
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}