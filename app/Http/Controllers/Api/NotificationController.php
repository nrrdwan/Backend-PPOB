<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseV1Service;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Ambil semua notifikasi milik user yang sedang login.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil notifikasi user login
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Tambahkan notifikasi baru (misalnya ketika deposit berhasil).
     */
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

        $user = User::find($request->user_id);

        if ($user && $user->fcm_token) {
            FirebaseV1Service::sendNotification(
                $user->fcm_token,
                $notification->title,
                $notification->message,
                ['notification_id' => $notification->id]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Notifikasi berhasil dibuat dan dikirim ke Firebase',
            'data' => $notification
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