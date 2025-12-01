<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'provider',
        'name',
        'is_favorite', // ✅ Tambahkan is_favorite
    ];

    protected $casts = [
        'is_favorite' => 'boolean',
    ];

    // ✅ Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}