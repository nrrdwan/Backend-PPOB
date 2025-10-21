<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseV1Service;
use App\Models\Notification;
use App\Models\User;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil notifikasi user
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'title' => $notif->title,
                    'description' => $notif->message,
                    'timestamp' => $notif->created_at->diffForHumans(), // contoh: "5 menit yang lalu"
                    'type' => $notif->type ?? 'today',
                    'is_read' => (bool) $notif->is_read,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'nullable|string'
        ]);

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'message' => $request->message,
            'type' => $request->type ?? 'info',
        ]);

        $user = \App\Models\User::find($request->user_id);

        if ($user && $user->fcm_token) {
            FirebaseV1Service::sendNotification(
                $user->fcm_token,
                $notification->title,
                $notification->message,
                ['notification_id' => $notification->id]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dibuat dan dikirim ke Firebase',
            'data' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'description' => $notification->message,
                'timestamp' => $notification->created_at->diffForHumans(),
                'type' => $notification->type,
            ],
        ], 201);
    }

    /**
     * Tandai notifikasi sebagai dibaca.
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->is_read = true;
        $notification->save();

        return response()->json([
            'status' => true,
            'message' => 'Notifikasi ditandai sebagai dibaca',
        ]);
    }

    /**
     * (Opsional) Hapus notifikasi.
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notifikasi dihapus',
        ]);
    }
}