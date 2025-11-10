<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BannerController extends Controller
{
    public function index()
    {
        try {
            Log::info('🔄 [BANNER API] Fetching active banners from database');

            $banners = Banner::active()
                ->valid()
                ->orderBy('created_at', 'desc')
                ->get([
                    'id', 
                    'title', 
                    'description', 
                    'image_url', 
                    'promo_code', 
                    'valid_until',
                    'terms_conditions',
                    'is_active',
                    'created_at',
                    'updated_at'
                ]);

            Log::info('📦 [BANNER API] Database query result:', [
                'count' => $banners->count(),
                'banners' => $banners->pluck('title')->toArray(),
                'active_status' => $banners->pluck('is_active')->toArray()
            ]);

            // Debug: Check each banner
            foreach ($banners as $banner) {
                Log::info('🔍 [BANNER DEBUG]', [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'is_active' => $banner->is_active,
                    'valid_until' => $banner->valid_until,
                    'image_url' => $banner->getRawOriginal('image_url'),
                    'image_url_full' => $banner->image_url_full,
                    'image_exists' => $banner->imageExists()
                ]);
            }

            $data = $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'promo_code' => $banner->promo_code,
                    'valid_until' => $banner->valid_until ? $banner->valid_until->toISOString() : null,
                    'terms_conditions' => $banner->terms_conditions,
                    'is_valid' => $banner->is_valid,
                    'image_url' => $banner->image_url_full,
                    'created_at' => $banner->created_at->toISOString(),
                    'updated_at' => $banner->updated_at->toISOString(),
                ];
            });

            Log::info('📤 [BANNER API] Final API response:', [
                'success' => true,
                'count' => $data->count(),
                'banner_titles' => $data->pluck('title')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'List of active banners',
                'data' => $data,
                'count' => $data->count()
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error fetching banners: ' . $e->getMessage());
            Log::error('❌ [BANNER API] Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banners',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::info("🔄 [BANNER API] Fetching banner detail for ID: {$id}");

            $banner = Banner::active()
                ->valid()
                ->find($id);

            if (!$banner) {
                Log::warning("⚠️ [BANNER API] Banner not found or inactive: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Banner not found or expired'
                ], 404);
            }

            $bannerData = [
                'id' => $banner->id,
                'title' => $banner->title,
                'description' => $banner->description,
                'promo_code' => $banner->promo_code,
                'valid_until' => $banner->valid_until ? $banner->valid_until->toISOString() : null,
                'terms_conditions' => $banner->terms_conditions,
                'is_valid' => $banner->is_valid,
                'image_url' => $banner->image_url_full,
                'created_at' => $banner->created_at->toISOString(),
                'updated_at' => $banner->updated_at->toISOString(),
            ];

            Log::info("✅ [BANNER API] Banner detail found:", ['banner_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Banner details',
                'data' => $bannerData,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [BANNER API] Error fetching banner detail {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banner detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'banner' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
            'valid_until' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->file('banner')->store('banners', 'public');
            
            Log::info('📁 [BANNER API] File stored:', [
                'original_name' => $request->file('banner')->getClientOriginalName(),
                'stored_path' => $path,
                'full_disk_path' => Storage::disk('public')->path($path)
            ]);
            
            $banner = Banner::create([
                'title' => $request->title,
                'description' => $request->description,
                'image_url' => $path,
                'promo_code' => $request->promo_code,
                'valid_until' => $request->valid_until,
                'terms_conditions' => $request->terms_conditions,
                'is_active' => true,
            ]);

            Log::info('✅ [BANNER API] Banner created:', [
                'id' => $banner->id, 
                'path' => $path,
                'full_url' => $banner->image_url_full
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'promo_code' => $banner->promo_code,
                    'valid_until' => $banner->valid_until,
                    'terms_conditions' => $banner->terms_conditions,
                    'is_valid' => $banner->is_valid,
                    'image_url' => $banner->image_url_full,
                ],
                'message' => 'Banner uploaded successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error creating banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'banner' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
            'valid_until' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'title' => $request->title ?? $banner->title,
                'description' => $request->description ?? $banner->description,
                'promo_code' => $request->promo_code ?? $banner->promo_code,
                'valid_until' => $request->valid_until ?? $banner->valid_until,
                'terms_conditions' => $request->terms_conditions ?? $banner->terms_conditions,
                'is_active' => $request->has('is_active') ? $request->is_active : $banner->is_active,
            ];

            if ($request->hasFile('banner')) {
                // Hapus file lama
                $oldImagePath = $banner->getRawOriginal('image_url');
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                    Log::info('🗑️ [BANNER API] Old image deleted:', ['path' => $oldImagePath]);
                }

                // Upload file baru
                $path = $request->file('banner')->store('banners', 'public');
                $updateData['image_url'] = $path;
                Log::info('📁 [BANNER API] New file stored:', ['path' => $path]);
            }

            $banner->update($updateData);

            Log::info('✏️ [BANNER API] Banner updated:', [
                'id' => $banner->id,
                'new_image_url' => $banner->image_url_full
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'description' => $banner->description,
                    'promo_code' => $banner->promo_code,
                    'valid_until' => $banner->valid_until,
                    'terms_conditions' => $banner->terms_conditions,
                    'is_valid' => $banner->is_valid,
                    'image_url' => $banner->image_url_full,
                ],
                'message' => 'Banner updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error updating banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $banner = Banner::find($id);
        if (!$banner) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }

        try {
            // Hapus file gambar
            $imagePath = $banner->getRawOriginal('image_url');
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
                Log::info('🗑️ [BANNER API] Banner image deleted:', ['path' => $imagePath]);
            }

            $banner->delete();
            
            Log::info('🗑️ [BANNER API] Banner deleted:', ['id' => $id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Banner deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error deleting banner: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all banners for admin (including inactive)
     */
    public function getAllBanners()
    {
        try {
            $banners = Banner::orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $banners,
                'count' => $banners->count()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error getting all banners: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get banners'
            ], 500);
        }
    }

    /**
     * Toggle banner status
     */
    public function toggleStatus($id)
    {
        try {
            $banner = Banner::find($id);
            if (!$banner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Banner not found'
                ], 404);
            }

            $banner->update(['is_active' => !$banner->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Banner status updated',
                'data' => [
                    'id' => $banner->id,
                    'is_active' => $banner->is_active
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [BANNER API] Error toggling banner status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle banner status'
            ], 500);
        }
    }
}