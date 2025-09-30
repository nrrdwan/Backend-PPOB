<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Get user balance
     */
    public function getBalance()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'Balance retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get balance error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve balance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Top up balance
     */
    public function topUp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000|max:10000000', // Min 10K, Max 10M
                'payment_method' => 'sometimes|string|in:bank_transfer,ewallet,qris',
                'notes' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            DB::beginTransaction();

            $amount = $request->amount;

            // Create transaction record for top up
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'product_id' => null,
                'phone_number' => null,
                'customer_id' => null,
                'amount' => $amount,
                'admin_fee' => 0,
                'total_amount' => $amount,
                'status' => 'success', // Simulate instant success for demo
                'type' => 'topup',
                'notes' => $request->notes ?? 'Top up saldo',
                'processed_at' => now()
            ]);

            // Add balance to user
            $user->addBalance($amount);

            // Log activity
            Log::info('Balance top up', [
                'user_id' => $user->id,
                'amount' => $amount,
                'transaction_id' => $transaction->transaction_id,
                'new_balance' => $user->balance
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Top up successful',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $amount,
                    'formatted_amount' => number_format($amount, 0, ',', '.'),
                    'new_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                    'payment_method' => $request->payment_method ?? 'bank_transfer'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Top up error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to top up balance',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get balance history (top up and spending)
     */
    public function getBalanceHistory(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'type' => 'sometimes|string|in:topup,purchase,all'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $query = Transaction::byUser($user->id)
                               ->with(['product'])
                               ->recent();

            // Filter by type if specified
            $type = $request->type ?? 'all';
            if ($type === 'topup') {
                $query->where('type', 'topup');
            } elseif ($type === 'purchase') {
                $query->whereIn('type', ['pulsa', 'pln', 'pdam', 'game', 'emoney', 'other']);
            }

            $limit = $request->limit ?? 20;
            $transactions = $query->paginate($limit);

            // Format transactions for response
            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->total_amount,
                    'formatted_amount' => $transaction->formatted_total_amount,
                    'status' => $transaction->status,
                    'description' => $this->getTransactionDescription($transaction),
                    'created_at' => $transaction->created_at,
                    'processed_at' => $transaction->processed_at
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Balance history retrieved successfully',
                'data' => [
                    'current_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                    'transactions' => $formattedTransactions,
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
            Log::error('Get balance history error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve balance history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get transaction description based on type and product
     */
    private function getTransactionDescription($transaction)
    {
        if ($transaction->type === 'topup') {
            return 'Top up saldo';
        }

        if ($transaction->product) {
            return $transaction->product->name;
        }

        return ucfirst($transaction->type) . ' transaction';
    }
}