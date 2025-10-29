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
use Illuminate\Support\Str;
use App\Models\QrToken;
use Carbon\Carbon;

class WalletController extends Controller
{
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
     * Top up balance - Create Midtrans transaction
     */
    public function topUp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'deposit_id' => 'sometimes|integer',
                'amount' => 'required|numeric|min:10000|max:10000000', // Min 10K, Max 10M
                'payment_method' => 'required|string', // ✅ Terima semua payment method
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

            $amount = (float) $request->amount;
            $paymentMethod = $request->payment_method;

            // ✅ Delegate ke MidtransController untuk membuat transaksi
            // kita buat Request baru dan ikutkan payment_method agar Midtrans tahu metode spesifik
            $user = $request->user();

            // Forward ke CoreMidtransController
            $coreController = new CoreMidtransController();
            return $coreController->createTransaction($request);

        } catch (\Exception $e) {
            Log::error('Top up error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create top up transaction',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function generateWithdrawCode(Request $request)
    {
        try {
            \Log::info('🔄 GENERATE WITHDRAW CODE REQUEST', $request->all());
            
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000',
                'phone_number' => 'required|string|max:20',
                'method_id' => 'required|in:INDOMARET,ALFAMART',
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

            $amount = (float) $request->amount;
            $method = strtoupper($request->method_id);
            $phoneNumber = $request->phone_number;

            // ✅ Validasi saldo cukup (tapi jangan kurangi dulu)
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi untuk melakukan penarikan'
                ], 400);
            }

            // ✅ Buat kode unik
            $timestamp = now()->format('ymd');
            $uniqueCode = sprintf(
                'MODI-%s-%05d-%s-%d',
                substr($method, 0, 3),
                $user->id,
                $timestamp,
                $amount
            );

            // ✅ Generate transaction ID
            $transactionId = 'TRX' . now()->format('Ymd') . 'TRI' . strtoupper(Str::random(4));

            // ✅ Simpan transaksi dengan status 'pending' (saldo belum berkurang)
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'type' => 'withdraw',
                'amount' => $amount,
                'total_amount' => $amount,
                'admin_fee' => 0,
                'status' => 'pending',
                'notes' => "Tarik tunai melalui $method - MENUNGGU KONFIRMASI",
                'metadata' => json_encode([
                    'method' => $method,
                    'phone_number' => $phoneNumber,
                    'withdraw_code' => $uniqueCode,
                    'pending_deduction' => true,
                ]),
            ]);

            \Log::info('✅ WITHDRAW CODE GENERATED', [
                'transaction_id' => $transaction->id,
                'withdraw_code' => $uniqueCode,
                'status' => 'pending'
            ]);

            // ✅ Tentukan durasi kadaluarsa (sinkron ke frontend)
            $expirySeconds = 60;

            // ✅ Kirim ke queue untuk auto-komplet setelah durasi ini
            \App\Jobs\AutoCompleteWithdraw::dispatch($transaction->id)
                ->delay(now()->addSeconds($expirySeconds));

            // ✅ Sertakan 'expires_in' supaya Flutter bisa tahu countdown-nya
            return response()->json([
                'success' => true,
                'message' => 'Kode penarikan berhasil dibuat',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'method' => $method,
                    'withdraw_code' => $uniqueCode,
                    'phone_number' => $phoneNumber,
                    'instruction' => "Tunjukkan kode berikut ke kasir $method untuk menarik tunai.",
                    'status' => 'pending',
                    'current_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                    'expires_in' => $expirySeconds, // ⏱️ Tambahan ini untuk sinkronisasi waktu
                    'expires_at' => now()->addSeconds($expirySeconds)->toISOString(), // bonus: waktu pasti kadaluarsa
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ GENERATE WITHDRAW CODE EXCEPTION', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat kode penarikan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function confirmWithdraw(Request $request)
    {
        try {
            \Log::info('🔄 CONFIRM WITHDRAW REQUEST', $request->all());
            \Log::info('🧠 DEBUG WITHDRAW CONFIRM INPUT', [
                'withdrawal_code' => $request->withdrawal_code,
            ]);

            $validator = Validator::make($request->all(), [
                'withdrawal_code' => 'required|string',
                'amount' => 'required|numeric|min:10000',
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
            $withdrawalCode = $request->withdrawal_code;
            $amount = (float) $request->amount;

            $transaction = Transaction::where('metadata->withdraw_code', $withdrawalCode)
                ->when(app()->environment('production'), fn($q) => $q->where('status', 'pending'))
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode penarikan tidak ditemukan atau sudah kadaluarsa',
                ], 404);
            }

            if (is_string($transaction->metadata)) {
                $transaction->metadata = json_decode($transaction->metadata, true);
            }
            $metadata = is_array($transaction->metadata)
                ? $transaction->metadata
                : [];

            if (empty($metadata['withdraw_code'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Metadata transaksi tidak valid atau hilang',
                ], 400);
            }

            // ✅ Validasi saldo cukup
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi untuk melakukan penarikan'
                ], 400);
            }

            DB::beginTransaction();

            // ✅ Kurangi saldo user
            $user->balance -= $amount;
            $user->save();

            // ✅ Update transaksi jadi success
            $transaction->update([
                'status' => 'success',
                'notes' => "Tarik tunai melalui {$metadata['method']}",
            ]);

            DB::commit();

            \Log::info('✅ WITHDRAW CONFIRMED', [
                'transaction_id' => $transaction->id,
                'withdraw_code' => $withdrawalCode,
                'new_balance' => $user->balance
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Penarikan tunai berhasil. Saldo telah dikurangi.',
                'data' => [
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'withdraw_code' => $withdrawalCode,
                    'status' => 'success',
                    'current_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ CONFIRM WITHDRAW EXCEPTION', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengonfirmasi penarikan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function generateQrCode(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

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
                'success' => true,
                'message' => 'QR code berhasil dibuat',
                'data' => [
                    'qr_payload' => $token,
                    'expires_at' => $expiresAt->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Generate QR error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat QR code',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function lookupQr(Request $request)
    {
        try {
            $token = $request->query('token');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak ditemukan',
                ], 400);
            }

            $record = QrToken::where('token', $token)->first();
            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR tidak valid',
                ], 400);
            }

            if ($record->used || $record->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR sudah kadaluarsa atau digunakan',
                ], 400);
            }

            $recipient = $record->recipient;

            return response()->json([
                'success' => true,
                'message' => 'QR valid',
                'data' => [
                    'recipient' => [
                        'id' => $recipient->id,
                        'name' => $recipient->name,
                        'phone' => $recipient->phone,
                        'avatar_url' => $recipient->avatar ?? null,
                    ],
                    'expires_at' => $record->expires_at->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Lookup QR error: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses QR',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function transferViaQr(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'qr_token' => 'required|string',
                'amount' => 'required|numeric|min:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $sender = Auth::user();
            if (!$sender) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $qrToken = $request->qr_token;
            $amount = (float) $request->amount;

            $record = QrToken::where('token', $qrToken)->first();
            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak valid',
                ], 400);
            }

            if ($record->used || $record->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR sudah kadaluarsa atau digunakan',
                ], 400);
            }

            $recipient = User::find($record->recipient_id);
            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penerima tidak ditemukan',
                ], 404);
            }

            if ($sender->id === $recipient->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat transfer ke diri sendiri',
                ], 400);
            }

            if ($sender->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak cukup',
                ], 400);
            }

            DB::beginTransaction();

            // Kurangi saldo pengirim
            $sender->decrement('balance', $amount);

            // Tambah saldo penerima
            $recipient->increment('balance', $amount);

            // Tandai token sudah digunakan
            $record->update(['used' => true, 'used_at' => now()]);

            // Catat transaksi
            Transaction::create([
                'from_user_id' => $sender->id,
                'to_user_id' => $recipient->id,
                'amount' => $amount,
                'type' => 'qr_transfer',
                'status' => 'success',
                'note' => 'Transfer via QR',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transfer berhasil',
                'data' => [
                    'from' => $sender->name,
                    'to' => $recipient->name,
                    'amount' => $amount,
                    'current_balance' => $sender->balance,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Transfer via QR error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat transfer via QR',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

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
                    'transaction_id' => $transaction->transaction_id ?? $transaction->id,
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

    public function withdraw(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000',
                'phone_number' => 'required|string|max:20',
                'method_id' => 'required|in:INDOMARET,ALFAMART',
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

            $amount = (float) $request->amount;
            $method = strtoupper($request->method_id);

            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi untuk melakukan penarikan'
                ], 400);
            }

            $timestamp = now()->format('ymdHis');
            $uniqueCode = sprintf(
                'WDR-%s-%05d-%s',
                substr($method, 0, 3),
                $user->id,
                $timestamp
            );

            DB::beginTransaction();

            $user->balance -= $amount;
            $user->save();

            $transaction = Transaction::create([
                'transaction_id' => $uniqueCode,
                'user_id' => $user->id,
                'type' => 'other', // withdraw belum termasuk enum, jadi pakai "other"
                'amount' => $amount,
                'admin_fee' => 0,
                'total_amount' => $amount,
                'status' => 'pending',
                'notes' => "Tarik tunai melalui $method | Nomor: {$request->phone_number}",
                'provider_response' => json_encode([
                    'method' => $method,
                    'phone_number' => $request->phone_number,
                    'withdraw_code' => $uniqueCode,
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permintaan penarikan berhasil dibuat',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $amount,
                    'method' => $method,
                    'withdraw_code' => $uniqueCode,
                    'instruction' => "Tunjukkan kode berikut ke kasir $method untuk menarik tunai.",
                    'status' => $transaction->status,
                    'current_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Withdraw error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan penarikan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function deductBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:0',
                'transaction_id' => 'required|string',
                'type' => 'required|string|in:pulsa,data,pln,pdam,game,emoney,other',
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

            $amount = (float) $request->amount;

            // Validasi saldo cukup
            if ($user->balance < $amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo tidak mencukupi untuk melakukan transaksi'
                ], 400);
            }

            DB::beginTransaction();

            // Kurangi saldo user
            $user->balance -= $amount;
            $user->save();

            DB::commit();

            Log::info('Balance deducted successfully', [
                'user_id' => $user->id,
                'amount' => $amount,
                'new_balance' => $user->balance,
                'transaction_id' => $request->transaction_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Saldo berhasil dikurangi',
                'data' => [
                    'previous_balance' => $user->balance + $amount,
                    'deducted_amount' => $amount,
                    'current_balance' => $user->balance,
                    'formatted_balance' => $user->formatted_balance,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Deduct balance error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengurangi saldo',
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