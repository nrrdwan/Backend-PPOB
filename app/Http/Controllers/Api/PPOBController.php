<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PPOBController extends Controller
{
    /**
     * Get all product categories
     */
    public function getCategories()
    {
        try {
            $categories = [
                [
                    'id' => 'pulsa',
                    'name' => 'Pulsa & Paket Data',
                    'icon' => 'phone',
                    'description' => 'Isi pulsa dan beli paket data untuk semua operator'
                ],
                [
                    'id' => 'pln',
                    'name' => 'PLN (Listrik)',
                    'icon' => 'zap',
                    'description' => 'Token listrik prabayar dan bayar tagihan pascabayar'
                ],
                [
                    'id' => 'pdam',
                    'name' => 'PDAM (Air)',
                    'icon' => 'droplet',
                    'description' => 'Pembayaran tagihan air PDAM'
                ],
                [
                    'id' => 'game',
                    'name' => 'Voucher Game',
                    'icon' => 'gamepad-2',
                    'description' => 'Top up game dan voucher digital'
                ],
                [
                    'id' => 'emoney',
                    'name' => 'E-Money & Dompet Digital',
                    'icon' => 'wallet',
                    'description' => 'Top up OVO, GoPay, DANA, ShopeePay, LinkAja'
                ],
                [
                    'id' => 'other',
                    'name' => 'Lainnya',
                    'icon' => 'grid-3x3',
                    'description' => 'BPJS, TV Berlangganan, Internet, dll'
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Categories retrieved successfully',
                'data' => [
                    'categories' => $categories
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get categories error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve categories',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get products by category
     */
    public function getProducts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category' => 'required|string|in:pulsa,pln,pdam,game,emoney,other',
                'provider' => 'sometimes|string',
                'search' => 'sometimes|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = Product::active()->byType($request->category);

            if ($request->has('provider')) {
                $query->byProvider($request->provider);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('selling_price', 'asc')->get();

            // Group by provider
            $groupedProducts = $products->groupBy('provider');

            return response()->json([
                'success' => true,
                'message' => 'Products retrieved successfully',
                'data' => [
                    'category' => $request->category,
                    'total_products' => $products->count(),
                    'providers' => $groupedProducts->map(function ($products, $provider) {
                        return [
                            'provider' => $provider,
                            'products' => $products->map(function ($product) {
                                return [
                                    'id' => $product->id,
                                    'code' => $product->code,
                                    'name' => $product->name,
                                    'type' => $product->type,
                                    'price' => $product->price,
                                    'admin_fee' => $product->admin_fee,
                                    'selling_price' => $product->selling_price,
                                    'formatted_price' => $product->formatted_price,
                                    'formatted_selling_price' => $product->formatted_selling_price,
                                    'description' => $product->description,
                                    'is_available' => $product->isAvailable(),
                                    'stock' => $product->is_unlimited ? null : $product->stock
                                ];
                            })
                        ];
                    })->values()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get products error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get product detail
     */
    public function getProductDetail($productId)
    {
        try {
            $product = Product::active()->find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Product detail retrieved successfully',
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'name' => $product->name,
                        'provider' => $product->provider,
                        'type' => $product->type,
                        'price' => $product->price,
                        'admin_fee' => $product->admin_fee,
                        'selling_price' => $product->selling_price,
                        'formatted_price' => $product->formatted_price,
                        'formatted_selling_price' => $product->formatted_selling_price,
                        'description' => $product->description,
                        'is_available' => $product->isAvailable(),
                        'stock' => $product->is_unlimited ? null : $product->stock,
                        'settings' => $product->settings
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get product detail error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product detail',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create purchase transaction
     */
    public function purchase(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'phone_number' => 'sometimes|string|max:20',
                'customer_id' => 'sometimes|string|max:50',
                'notes' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $user = Auth::user();
            $product = Product::find($request->product_id);

            // Check product availability
            if (!$product->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product is not available'
                ], 400);
            }

            // Check user balance (assuming user has wallet)
            $totalAmount = $product->selling_price;

            /** @var \App\Models\User $user */
            $user = $request->user();

            if (!$user->hasBalance($totalAmount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance',
                    'data' => [
                        'required_amount' => $totalAmount,
                        'current_balance' => $user->balance,
                        'shortage' => $totalAmount - $user->balance
                    ]
                ], 400);
            }

            // Create transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'phone_number' => $request->phone_number,
                'customer_id' => $request->customer_id,
                'amount' => $product->price,
                'admin_fee' => $product->admin_fee,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'type' => $product->type,
                'notes' => $request->notes
            ]);

            // Reduce stock if not unlimited
            $product->reduceStock();

            // Deduct balance from user
            $user->deductBalance($totalAmount);

            // Log activity
            Log::info('Transaction created', [
                'transaction_id' => $transaction->transaction_id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'amount' => $totalAmount
            ]);

            DB::commit();

            // Process payment (simulate for now)
            $this->processTransaction($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                        'product_name' => $product->name,
                        'amount' => $transaction->amount,
                        'admin_fee' => $transaction->admin_fee,
                        'total_amount' => $transaction->total_amount,
                        'formatted_total_amount' => $transaction->formatted_total_amount,
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'phone_number' => $transaction->phone_number,
                        'customer_id' => $transaction->customer_id,
                        'created_at' => $transaction->created_at
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Purchase error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getTransactionStatus($transactionId)
    {
        try {
            $user = Auth::user();
            $transaction = Transaction::where('transaction_id', $transactionId)
                ->where('user_id', $user->id)
                ->with(['product'])
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaction status retrieved successfully',
                'data' => [
                    'transaction' => [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                        'product' => [
                            'name' => $transaction->product->name,
                            'type' => $transaction->product->type,
                            'provider' => $transaction->product->provider
                        ],
                        'amount' => $transaction->amount,
                        'admin_fee' => $transaction->admin_fee,
                        'total_amount' => $transaction->total_amount,
                        'formatted_total_amount' => $transaction->formatted_total_amount,
                        'status' => $transaction->status,
                        'type' => $transaction->type,
                        'phone_number' => $transaction->phone_number,
                        'customer_id' => $transaction->customer_id,
                        'notes' => $transaction->notes,
                        'provider_response' => $transaction->provider_response,
                        'processed_at' => $transaction->processed_at,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get transaction status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user transaction history
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|string|in:pending,processing,success,failed,cancelled',
                'type' => 'sometimes|string|in:pulsa,pln,pdam,game,emoney,other',
                'limit' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $query = Transaction::byUser($user->id)->with(['product'])->recent();

            if ($request->has('status')) {
                $query->byStatus($request->status);
            }

            if ($request->has('type')) {
                $query->byType($request->type);
            }

            $limit = $request->limit ?? 20;
            $transactions = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'message' => 'Transaction history retrieved successfully',
                'data' => [
                    'transactions' => $transactions->items(),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Get transaction history error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve transaction history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Simulate transaction processing
     * In real implementation, this would integrate with actual provider APIs
     */
    private function processTransaction(Transaction $transaction)
    {
        // Mark as processing
        $transaction->markAsProcessing();

        // Simulate processing delay and random success/failure
        // In real implementation, integrate with provider APIs here
        $isSuccess = rand(1, 10) > 2; // 80% success rate for demo

        if ($isSuccess) {
            $providerResponse = [
                'status' => 'success',
                'reference_id' => 'REF' . now()->format('YmdHis') . rand(1000, 9999),
                'message' => 'Transaction processed successfully',
                'processed_at' => now()->toISOString()
            ];
            $transaction->markAsSuccess($providerResponse);
        } else {
            $providerResponse = [
                'status' => 'failed',
                'error_code' => 'PROVIDER_ERROR',
                'message' => 'Transaction failed from provider',
                'processed_at' => now()->toISOString()
            ];
            $transaction->markAsFailed($providerResponse);
        }

        Log::info('Transaction processed', [
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'provider_response' => $providerResponse
        ]);
    }
}
