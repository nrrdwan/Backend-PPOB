<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;

class QrController extends Controller
{
    // Generate QR untuk akun user (penerima)
    public function generate(Request $request)
    {
        $user = $request->user();
        $expiresIn = $request->input('expires_in', 300); // default 5 menit
        $expiresAt = Carbon::now()->addSeconds($expiresIn);
        $nonce = Str::uuid()->toString();

        $payload = [
            'recipient_id' => $user->id,
            'nonce' => $nonce,
            'iat' => now()->timestamp,
            'exp' => $expiresAt->timestamp,
        ];

        $token = base64_encode(json_encode($payload));

        QrToken::create([
            'recipient_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'qr_payload' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    // Lookup untuk menampilkan info penerima (saat discan)
    public function lookup(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return response()->json(['is_valid' => false, 'message' => 'Token tidak ditemukan'], 400);
        }

        $record = QrToken::where('token', $token)->first();
        if (!$record) {
            return response()->json(['is_valid' => false, 'message' => 'QR tidak valid'], 400);
        }

        if ($record->used || $record->expires_at->isPast()) {
            return response()->json(['is_valid' => false, 'message' => 'QR sudah tidak berlaku'], 400);
        }

        $recipient = $record->recipient;
        return response()->json([
            'is_valid' => true,
            'recipient' => [
                'id' => $recipient->id,
                'name' => $recipient->name,
                'phone' => $recipient->phone,
                'avatar_url' => $recipient->avatar ?? null,
            ],
            'expires_at' => $record->expires_at->toIso8601String(),
        ]);
    }
}