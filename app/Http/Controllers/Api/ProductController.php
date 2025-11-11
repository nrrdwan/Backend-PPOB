<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            Log::info('🔄 [PRODUCT API] Fetching products with filters', [
                'type' => $request->type,
                'provider' => $request->provider
            ]);

            $query = Product::query();
            
            // Filter by type
            if ($request->has('type') && $request->type) {
                $query->where('type', $request->type);
            }
            
            // Filter by provider
            if ($request->has('provider') && $request->provider) {
                $query->where('provider', $request->provider);
            }
            
            // Only active products
            $query->where('is_active', true);
            
            // Order by selling price
            $query->orderBy('selling_price', 'asc');
            
            $products = $query->get();

            Log::info('📦 [PRODUCT API] Database query result:', [
                'count' => $products->count(),
                'products' => $products->pluck('name')->toArray(),
                'types' => $products->pluck('type')->toArray()
            ]);

            // Debug: Check each product
            foreach ($products as $product) {
                Log::info('🔍 [PRODUCT DEBUG]', [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'type' => $product->type,
                    'provider' => $product->provider,
                    'price' => $product->price,
                    'selling_price' => $product->selling_price,
                    'is_active' => $product->is_active,
                    'is_available' => $product->isAvailable()
                ]);
            }

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'provider' => $product->provider,
                    'type' => $product->type,
                    'price' => (float) $product->price,
                    'admin_fee' => (float) $product->admin_fee,
                    'selling_price' => (float) $product->selling_price,
                    'description' => $product->description,
                    'is_active' => (bool) $product->is_active,
                    'stock' => $product->stock,
                    'is_unlimited' => (bool) $product->is_unlimited,
                    'settings' => $product->settings,
                    'is_available' => $product->isAvailable(),
                    'formatted_price' => $product->formatted_price,
                    'formatted_selling_price' => $product->formatted_selling_price,
                    'profit' => $product->selling_price - $product->price - $product->admin_fee,
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString(),
                ];
            });

            Log::info('📤 [PRODUCT API] Final API response:', [
                'success' => true,
                'count' => $data->count(),
                'product_names' => $data->pluck('name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => $data,
                'count' => $data->count()
            ], 200, [], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            Log::error('❌ [PRODUCT API] Error fetching products: ' . $e->getMessage());
            Log::error('❌ [PRODUCT API] Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            Log::info("🔄 [PRODUCT API] Fetching product detail for ID: {$id}");

            $product = Product::where('is_active', true)->find($id);

            if (!$product) {
                Log::warning("⚠️ [PRODUCT API] Product not found or inactive: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found or inactive'
                ], 404);
            }

            $productData = [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'provider' => $product->provider,
                'type' => $product->type,
                'price' => (float) $product->price,
                'admin_fee' => (float) $product->admin_fee,
                'selling_price' => (float) $product->selling_price,
                'description' => $product->description,
                'is_active' => (bool) $product->is_active,
                'stock' => $product->stock,
                'is_unlimited' => (bool) $product->is_unlimited,
                'settings' => $product->settings,
                'is_available' => $product->isAvailable(),
                'formatted_price' => $product->formatted_price,
                'formatted_selling_price' => $product->formatted_selling_price,
                'profit' => $product->selling_price - $product->price - $product->admin_fee,
                'created_at' => $product->created_at->toISOString(),
                'updated_at' => $product->updated_at->toISOString(),
            ];

            Log::info("✅ [PRODUCT API] Product detail found:", ['product_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Product details',
                'data' => $productData,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [PRODUCT API] Error fetching product detail {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch product detail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($type)
    {
        try {
            Log::info("🔄 [PRODUCT API] Fetching products by type: {$type}");

            $products = Product::where('type', $type)
                ->where('is_active', true)
                ->orderBy('selling_price', 'asc')
                ->get();

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'provider' => $product->provider,
                    'type' => $product->type,
                    'price' => (float) $product->price,
                    'admin_fee' => (float) $product->admin_fee,
                    'selling_price' => (float) $product->selling_price,
                    'description' => $product->description,
                    'is_active' => (bool) $product->is_active,
                    'stock' => $product->stock,
                    'is_unlimited' => (bool) $product->is_unlimited,
                    'settings' => $product->settings,
                    'is_available' => $product->isAvailable(),
                    'formatted_price' => $product->formatted_price,
                    'formatted_selling_price' => $product->formatted_selling_price,
                    'profit' => $product->selling_price - $product->price - $product->admin_fee,
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString(),
                ];
            });

            Log::info("✅ [PRODUCT API] Products by type {$type}:", [
                'count' => $data->count(),
                'products' => $data->pluck('name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Products with type {$type}",
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [PRODUCT API] Error fetching products by type {$type}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByProvider($provider)
    {
        try {
            Log::info("🔄 [PRODUCT API] Fetching products by provider: {$provider}");

            $products = Product::where('provider', $provider)
                ->where('is_active', true)
                ->orderBy('selling_price', 'asc')
                ->get();

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'provider' => $product->provider,
                    'type' => $product->type,
                    'price' => (float) $product->price,
                    'admin_fee' => (float) $product->admin_fee,
                    'selling_price' => (float) $product->selling_price,
                    'description' => $product->description,
                    'is_active' => (bool) $product->is_active,
                    'stock' => $product->stock,
                    'is_unlimited' => (bool) $product->is_unlimited,
                    'settings' => $product->settings,
                    'is_available' => $product->isAvailable(),
                    'formatted_price' => $product->formatted_price,
                    'formatted_selling_price' => $product->formatted_selling_price,
                    'profit' => $product->selling_price - $product->price - $product->admin_fee,
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString(),
                ];
            });

            Log::info("✅ [PRODUCT API] Products by provider {$provider}:", [
                'count' => $data->count(),
                'products' => $data->pluck('name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Products from provider {$provider}",
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ [PRODUCT API] Error fetching products by provider {$provider}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all products for admin (including inactive)
     */
    public function getAllProducts()
    {
        try {
            $products = Product::orderBy('created_at', 'desc')->get();

            $data = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'provider' => $product->provider,
                    'type' => $product->type,
                    'price' => (float) $product->price,
                    'admin_fee' => (float) $product->admin_fee,
                    'selling_price' => (float) $product->selling_price,
                    'description' => $product->description,
                    'is_active' => (bool) $product->is_active,
                    'stock' => $product->stock,
                    'is_unlimited' => (bool) $product->is_unlimited,
                    'settings' => $product->settings,
                    'is_available' => $product->isAvailable(),
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [PRODUCT API] Error getting all products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get products'
            ], 500);
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus($id)
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $product->update(['is_active' => !$product->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Product status updated',
                'data' => [
                    'id' => $product->id,
                    'is_active' => $product->is_active
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ [PRODUCT API] Error toggling product status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle product status'
            ], 500);
        }
    }
}