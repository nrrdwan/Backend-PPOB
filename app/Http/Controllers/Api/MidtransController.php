<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\MidtransService;
use Illuminate\Support\Str;

class MidtransController extends Controller
{
    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
        
        Log::info('🔧 [Midtrans] Controller initialized with Service');
    }

    public function getPaymentDetails($transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        try {
            // Get real status from Midtrans
            $midtransData = $this->midtransService->getStatus($transactionId);
            $paymentDetails = $this->midtransService->extractPaymentDetails($midtransData);
            
            // Determine payment type for Flutter routing
            $paymentType = $this->mapToFlutterPaymentType($midtransData['payment_type'] ?? '', $midtransData);

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->amount,
                    'total_amount' => $transaction->total_amount,
                    'status' => $transaction->status,
                    'payment_type' => $paymentType,
                    'payment_details' => $paymentDetails,
                    'midtrans_data' => $midtransData
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get payment details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createBankTransfer(Request $request)
    {
        Log::info('🔵 [Midtrans] createBankTransfer called', ['request' => $request->all()]);

        $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000',
            'bank_type' => 'required|string|in:bca,bni,bri,mandiri,permata,other'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $amount = $request->amount;
        $bankType = $request->bank_type;
        $adminFee = $this->calculateAdminFee($amount);
        $totalAmount = $amount + $adminFee;

        // Create transaction record
        $transaction = Transaction::create([
            'transaction_id' => 'BANK-' . time() . '-' . $user->id . '-' . Str::random(6),
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'channel' => 'bank_transfer|' . $bankType
        ]);

        // Prepare transaction data for Midtrans
        $transactionData = [
            'order_id' => $transaction->transaction_id,
            'gross_amount' => $totalAmount,
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '081234567890',
            ],
            'item_details' => [
                [
                    'id' => 'topup',
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Top Up Saldo',
                ],
                [
                    'id' => 'admin_fee',
                    'price' => $adminFee,
                    'quantity' => 1,
                    'name' => 'Biaya Admin',
                ]
            ]
        ];

        try {
            // ✅ GUNAKAN CORE API BANK TRANSFER
            $midtransResponse = $this->midtransService->createBankTransfer($transactionData, $bankType);
            $paymentDetails = $this->midtransService->extractPaymentDetails($midtransResponse);

            // Update transaction dengan data dari Midtrans
            $transaction->update([
                'reference_id' => $midtransResponse['transaction_id'] ?? null,
                'callback_data' => json_encode($midtransResponse)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank transfer transaction created successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'total_amount' => $totalAmount,
                    'bank_type' => $bankType,
                    'midtrans_response' => $midtransResponse,
                    'payment_details' => $paymentDetails
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('🔴 [Midtrans] createBankTransfer exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $transaction->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat transaksi transfer bank. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function mapToFlutterPaymentType($midtransPaymentType, $midtransData)
    {
        $typeMap = [
            'gopay' => 'ewallet',
            'shopeepay' => 'ewallet', 
            'dana' => 'ewallet',
            'linkaja' => 'ewallet',
            'qris' => 'qris',
            'bank_transfer' => 'va',
            'echannel' => 'bank_transfer', // Mandiri bill
            'cstore' => 'counter'
        ];

        $type = $typeMap[$midtransPaymentType] ?? 'bank_transfer';
        
        // For bank transfer, check specific bank
        if ($type === 'va' && isset($midtransData['va_numbers'][0]['bank'])) {
            $bank = $midtransData['va_numbers'][0]['bank'];
            // You can add specific logic for different VA banks here
        }

        return $type;
    }
   
    /**
     * Create Snap Token for Top Up
     */
    public function createSnapToken(Request $request)
    {
        Log::info('🔵 [Midtrans] createSnapToken called', ['request' => $request->all()]);

        $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000',
            'payment_method' => 'nullable|string'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $amount = $request->amount;
        $adminFee = $this->calculateAdminFee($amount);
        $totalAmount = $amount + $adminFee;

        if ($this->isMockMode($amount)) {
            return $this->createMockTransaction($user, $amount, $adminFee, $totalAmount);
        }

        // Save requested payment method so we know what user selected
        $requestedPaymentMethod = $request->payment_method ?? null;

        $transaction = Transaction::create([
            'transaction_id' => 'TOPUP-' . time() . '-' . $user->id . '-' . Str::random(6),
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            // store requested payment method appended to channel for traceability
            'channel' => $requestedPaymentMethod ? 'midtrans|' . $requestedPaymentMethod : 'midtrans'
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_id,
                'gross_amount' => $totalAmount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '081234567890',
            ],
            'item_details' => [
                [
                    'id' => 'topup',
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'Top Up Saldo',
                ],
                [
                    'id' => 'admin_fee',
                    'price' => $adminFee,
                    'quantity' => 1,
                    'name' => 'Biaya Admin',
                ]
            ],
            'enabled_payments' => [
                'gopay','shopeepay','dana','linkaja','jenius','qris',
                'bca_va','bni_va','bri_va','mandiri_va','permata_va','other_va'
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/api/midtrans/finish',
                'error' => config('app.url') . '/api/midtrans/error',
                'pending' => config('app.url') . '/api/midtrans/pending'
            ]
        ];

        // 🔹 Override enabled_payments kalau ada payment_method spesifik (dari frontend)
        if ($requestedPaymentMethod) {
            $normalized = strtolower(str_replace(' ', '_', $requestedPaymentMethod));
            $params['enabled_payments'] = [$normalized];
        }

        try {
        // ✅ GUNAKAN SERVICE
        $snapToken = $this->midtransService->createSnapToken($params);
        $transaction->update(['reference_id' => $snapToken]);

        // ✅ GUNAKAN SERVICE UNTUK FETCH STATUS
        $midtransStatus = $this->midtransService->getStatus($transaction->transaction_id);
        $paymentDetails = $this->midtransService->extractPaymentDetails($midtransStatus);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => [
                'snap_token' => $snapToken,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'midtrans_status' => $midtransStatus,
                'payment_details' => $paymentDetails
            ]
        ]);

        } catch (\Exception $e) {
            Log::error('🔴 [Midtrans] createSnapToken exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $transaction->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function manualSuccess(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10000|max:10000000']);
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false,'message' => 'Unauthorized'],401);
        }

        $amount = $request->amount;
        $adminFee = $this->calculateAdminFee($amount);
        $totalAmount = $amount + $adminFee;

        DB::beginTransaction();
        $transaction = Transaction::create([
            'transaction_id' => 'MANUAL-' . time() . '-' . $user->id,
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'success',
            'channel' => 'manual_test',
            'processed_at' => now()
        ]);
        $user->increment('balance', $amount);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Manual top up successful',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
                'old_balance' => $user->balance - $amount,
                'new_balance' => $user->balance,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount
            ]
        ]);
    }

    public function getStatus($transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        if (!$transaction) {
            return response()->json(['success' => false,'message' => 'Transaction not found'],404);
        }

        try {
            // ✅ GUNAKAN SERVICE
            $midtransData = $this->midtransService->getStatus($transactionId);
            $paymentDetails = $this->midtransService->extractPaymentDetails($midtransData);

            return response()->json([
                'success' => true,
                'transaction' => [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'total_amount' => $transaction->total_amount,
                    'created_at' => $transaction->created_at,
                    'processed_at' => $transaction->processed_at
                ],
                'midtrans' => $midtransData,
                'payment_details' => $paymentDetails
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateSignature(Request $request)
    {
        $request->validate(['order_id' => 'required|string','gross_amount' => 'required|string']);
        $orderId = $request->order_id;
        $statusCode = $request->status_code ?? '200';
        $grossAmount = $request->gross_amount;
        
        // ✅ GUNAKAN SERVER KEY DARI SERVICE ATAU CONFIG
        $serverKey = config('services.midtrans.server_key');
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return response()->json([
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $hash
        ]);
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        
        // ✅ HAPUS VERIFIKASI SIGNATURE UNTUK SEMENTARA ATAU PINDAHKAN KE SERVICE
        // if (!$this->verifySignature($payload)) {
        //     return response()->json(['message' => 'Invalid signature'], 401);
        // }

        $orderId = $payload['order_id'];
        $transactionStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? '';

        $transaction = Transaction::where('transaction_id', $orderId)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        DB::beginTransaction();
        if ($transactionStatus == 'capture' && $fraudStatus == 'accept') {
            $this->processSuccessfulPayment($transaction);
        } else if ($transactionStatus == 'settlement') {
            $this->processSuccessfulPayment($transaction);
        } else if ($transactionStatus == 'pending') {
            $transaction->update(['status' => 'pending']);
        } else if (in_array($transactionStatus, ['deny','expire','cancel'])) {
            $transaction->update(['status' => 'failed']);
        }
        $transaction->update([
            'callback_data' => json_encode($payload),
            'processed_at' => now(),
            'channel' => $payload['payment_type'] ?? $transaction->channel
        ]);
        DB::commit();

        return response()->json(['message' => 'OK']);
    }

    public function debugNotification(Request $request)
    {
        return response()->json([
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'query' => $request->query->all(),
            'request' => $request->request->all(),
            'all' => $request->all(),
            'json' => $request->json()->all(),
            'content' => $request->getContent(),
            'content_type' => $request->header('Content-Type')
        ]);
    }

    /**
     * ✅ Get transaction status (Realtime dari Midtrans)
     */

    private function processSuccessfulPayment($transaction)
    {
        $transaction->update(['status' => 'success']);
        $transaction->user->increment('balance', $transaction->amount);
    }

    private function calculateAdminFee($amount)
    {
        if ($amount <= 50000) return 2500;
        else if ($amount <= 100000) return 5000;
        else return 7500;
    }

    private function isMockMode($amount)
    {
        $mockAmounts = [10001,10002,10003,50001,50002,50003,100001,100002,100003];
        return in_array($amount, $mockAmounts);
    }

    private function createMockTransaction($user, $amount, $adminFee, $totalAmount)
    {
        DB::beginTransaction();
        $transaction = Transaction::create([
            'transaction_id' => 'MOCK-' . time() . '-' . $user->id,
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'success',
            'channel' => 'mock_development',
            'reference_id' => 'mock-token-' . Str::random(10),
            'processed_at' => now()
        ]);
        $user->increment('balance', $amount);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Mock payment successful (Development Mode)',
            'data' => [
                'snap_token' => 'mock-token-' . Str::random(10),
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'mock_mode' => true,
                'old_balance' => $user->balance - $amount,
                'new_balance' => $user->balance
            ]
        ]);
    }
}