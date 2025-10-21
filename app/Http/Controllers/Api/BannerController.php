<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::where('is_active', true)->get(['id', 'title', 'image_url']);

        $banners->transform(function ($banner) {
            if ($banner->image_url && !str_contains($banner->image_url, 'http')) {
                $banner->image_url = asset('storage/' . ltrim($banner->image_url, '/'));
            }

            \Log::info('✅ Banner transformed: ' . $banner->image_url);

            return $banner;
        });

        return response()->json(['data' => $banners], 200);
    }

    // Upload banner baru
    public function store(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'nullable|string|max:255',
        ]);

        // Simpan file ke storage lokal (public disk)
        $path = $request->file('banner')->store('banners', 'public');
        $url = asset('storage/' . $path);

        $banner = Banner::create([
            'title' => $request->title,
            'image_url' => $url,
        ]);

        return response()->json(['data' => $banner, 'message' => 'Banner uploaded successfully'], 201);
    }

    // Hapus banner
    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['message' => 'Banner not found'], 404);
        }

        // Hapus file dari storage
        $filePath = str_replace(asset('storage/'), '', $banner->image_url);
        Storage::disk('public')->delete($filePath);

        $banner->delete();
        return response()->json(['message' => 'Banner deleted successfully'], 200);
    }
}
