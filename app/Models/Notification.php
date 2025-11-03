<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->diffForHumans() : null;
    }

    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true]);
            Log::info('📬 [Notification] Notifikasi ditandai telah dibaca', [
                'id' => $this->id,
                'user_id' => $this->user_id,
            ]);
        }
    }

    public static function sendAndSave($user, $title, $message, $type = 'general', $data = [])
    {
        $notification = self::create([
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);

        try {
            if ($user->fcm_token) {
                \App\Services\FirebaseV1Service::sendNotification(
                    $user->fcm_token,
                    $title,
                    $message,
                    array_merge($data, [
                        'notification_id' => $notification->id,
                        'type' => $type,
                    ])
                );
                Log::info('📱 [FCM] Notifikasi dikirim melalui helper Notification::sendAndSave', [
                    'user_id' => $user->id,
                    'notification_id' => $notification->id,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('❌ [Notification] Gagal mengirim FCM', ['error' => $e->getMessage()]);
        }

        return $notification;
    }
}