<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use Illuminate\Support\Facades\Log;

class AboutUsController extends Controller
{
    /**
     * Get all active About Us items
     */
    public function index()
    {
        try {
            Log::info('🔄 [ABOUT US API] Fetching active items');

            $items = AboutUs::active()
                ->ordered()
                ->get();

            Log::info('📦 [ABOUT US API] Query result:', [
                'count' => $items->count(),
                'items' => $items->pluck('title')->toArray(),
            ]);

            $data = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'link' => $item->formatted_link,
                    'icon_url' => $item->icon_url,
                    'order' => $item->order,
                    'is_active' => $item->is_active,
                ];
            });

            Log::info('📤 [ABOUT US API] Response:', [
                'success' => true,
                'count' => $data->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'About Us items retrieved successfully',
                'data' => $data,
                'count' => $data->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [ABOUT US API] Error: ' . $e->getMessage());
            Log::error('❌ [ABOUT US API] Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch About Us items',
                'error' => $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get specific About Us item by type
     */
    public function getByType($type)
    {
        try {
            Log::info("🔄 [ABOUT US API] Fetching item by type: {$type}");

            $item = AboutUs::active()
                ->where('type', $type)
                ->first();

            if (!$item) {
                Log::warning("⚠️ [ABOUT US API] Item not found: {$type}");
                return response()->json([
                    'success' => false,
                    'message' => 'About Us item not found',
                ], 404);
            }

            $data = [
                'id' => $item->id,
                'type' => $item->type,
                'title' => $item->title,
                'description' => $item->description,
                'link' => $item->formatted_link,
                'icon_url' => $item->icon_url,
                'order' => $item->order,
                'is_active' => $item->is_active,
            ];

            Log::info("✅ [ABOUT US API] Item found: {$type}");

            return response()->json([
                'success' => true,
                'message' => 'About Us item retrieved successfully',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [ABOUT US API] Error fetching type {$type}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch About Us item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}