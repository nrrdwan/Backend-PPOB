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
        $banners = \App\Models\Banner::where('is_active', true)->get(['id', 'title', 'image_url']);

        $data = $banners->map(function ($banner) {
            $path = str_replace(['public/', 'storage/'], '', $banner->image_url);
            $path = ltrim($path, '/');
            $fullUrl = config('app.url') . '/storage/' . $path;

            \Log::info('🎯 Final full URL generated: ' . $fullUrl);

            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $fullUrl,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'List of active banners',
            'data' => $data,
        ]);
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
