<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::where('is_active', true)->get(['id', 'title', 'image_url']);

        $data = $banners->map(function ($banner) {
            // 🔥 DEBUGGING: Log nilai asli dari database
            Log::info('📦 Banner dari DB:', [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url_raw' => $banner->image_url
            ]);

            // 🎯 LOGIC BARU: Deteksi apakah sudah full URL atau masih path
            $imageUrl = $banner->image_url;
            
            // Jika sudah full URL (dimulai dengan http/https), pakai langsung
            if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
                $fullUrl = $imageUrl;
            } else {
                // Jika masih path, convert ke full URL
                // Hapus prefix 'public/' atau 'storage/' jika ada
                $cleanPath = str_replace(['public/', 'storage/'], '', $imageUrl);
                $cleanPath = ltrim($cleanPath, '/');
                
                // Pastikan path dimulai dengan 'banners/'
                if (!str_starts_with($cleanPath, 'banners/')) {
                    $cleanPath = 'banners/' . $cleanPath;
                }
                
                $fullUrl = config('app.url') . '/storage/' . $cleanPath;
            }

            Log::info('🎯 Final full URL generated: ' . $fullUrl);

            return [
                'id' => $banner->id,
                'title' => $banner->title,
                'image_url' => $fullUrl,
            ];
        });

        // 🔥 Log response final
        Log::info('📤 Response banners:', ['data' => $data->toArray()]);

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
        
        // Simpan HANYA path relatif ke database (bukan full URL)
        $banner = Banner::create([
            'title' => $request->title,
            'image_url' => $path, // Contoh: banners/abc123.jpg
        ]);

        Log::info('✅ Banner created:', ['id' => $banner->id, 'path' => $path]);

        return response()->json([
            'data' => $banner, 
            'message' => 'Banner uploaded successfully'
        ], 201);
    }

    // Hapus banner
    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json(['message' => 'Banner not found'], 404);
        }

        // Hapus file dari storage
        $filePath = str_replace(['public/', 'storage/', config('app.url') . '/storage/'], '', $banner->image_url);
        Storage::disk('public')->delete($filePath);

        $banner->delete();
        
        Log::info('🗑️ Banner deleted:', ['id' => $id]);
        
        return response()->json(['message' => 'Banner deleted successfully'], 200);
    }
}