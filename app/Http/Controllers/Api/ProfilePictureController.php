<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfilePicture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfilePictureController extends Controller
{
    /**
     * Upload atau update foto profil user.
     * Jika user sudah pernah upload, hapus yang lama.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $old = ProfilePicture::where('user_id', $user->id)->first();
        if ($old) {
            $oldPath = str_replace(asset('storage/'), '', $old->image_url);
            Storage::disk('public')->delete($oldPath);
            $old->delete();
        }

        $path = $request->file('profile_picture')->store('profile_pictures', 'public');
        $url = asset('storage/' . $path);

        $profilePicture = ProfilePicture::create([
            'user_id' => $user->id,
            'image_url' => $url,
        ]);

        $user->update(['profile_picture' => $url]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data' => [
                'profile_picture_url' => $url,
            ],
        ], 200);
    }

    /**
     * Hapus foto profil user (opsional)
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $profile = ProfilePicture::where('user_id', $user->id)->first();
        if (!$profile) {
            return response()->json(['message' => 'Tidak ada foto profil untuk dihapus'], 404);
        }

        $filePath = str_replace(asset('storage/'), '', $profile->image_url);
        Storage::disk('public')->delete($filePath);
        $profile->delete();

        $user->update(['profile_picture' => null]);

        return response()->json(['message' => 'Foto profil berhasil dihapus'], 200);
    }

    /**
     * Ambil foto profil user (opsional)
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $profile = ProfilePicture::where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
            'data' => [
                'profile_picture_url' => $profile?->image_url ?? null,
            ],
        ]);
    }
}