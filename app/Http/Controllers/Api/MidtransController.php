<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransController extends Controller
{
    private $serverKey;
    private $isProduction;
    
    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->isProduction = config('services.midtrans.is_production');
    }
    
    /**
     * Create Snap Token for Top Up
     */
    public function createSnapToken(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:10000000', // Min 10k, Max 10jt
        ]);

        $user = $request->user();
        $amount = $request->amount;
        $adminFee = $this->calculateAdminFee($amount);
        $totalAmount = $amount + $adminFee;
        
        // Create transaction record
        $transaction = Transaction::create([
            'transaction_id' => 'TOPUP-' . time() . '-' . $user->id,
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => $amount,
            'admin_fee' => $adminFee,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'channel' => 'midtrans'
        ]);

        // Midtrans transaction details
        $params = [
            'transaction_details' => [
                'order_id' => $transaction->transaction_id,
                'gross_amount' => $totalAmount,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
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
                'gopay', 'shopeepay', 'dana', 'linkaja', 'jenius', 'qris',
                'bca_va', 'bni_va', 'bri_va', 'mandiri_va', 'permata_va',
                'other_va'
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/api/midtrans/finish',
                'error' => config('app.url') . '/api/midtrans/error',
                'pending' => config('app.url') . '/api/midtrans/pending'
            ]
        ];

        try {
            $snapToken = $this->getSnapToken($params);
            
            // Update transaction with snap token
            $transaction->update([
                'reference_id' => $snapToken
            ]);

            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Handle Midtrans Notification
     */
    public function notification(Request $request)
    {
        // Handle both application/json and text/plain content types
        if ($request->header('Content-Type') === 'text/plain') {
            $payload = $request->json()->all();
        } else {
            $payload = $request->all();
        }
        
        // Log received payload for debugging
        Log::info('Midtrans notification received', [
            'content_type' => $request->header('Content-Type'),
            'payload' => $payload
        ]);
        
        // Verify signature
        if (!$this->verifySignature($payload)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $orderId = $payload['order_id'];
        $transactionStatus = $payload['transaction_status'];
        $fraudStatus = $payload['fraud_status'] ?? '';

        try {
            $transaction = Transaction::where('transaction_id', $orderId)->first();
            
            if (!$transaction) {
                Log::warning('Transaction not found: ' . $orderId);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            DB::beginTransaction();

            // Update transaction status based on Midtrans response
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $transaction->update(['status' => 'challenge']);
                } else if ($fraudStatus == 'accept') {
                    $this->processSuccessfulPayment($transaction);
                }
            } else if ($transactionStatus == 'settlement') {
                $this->processSuccessfulPayment($transaction);
            } else if ($transactionStatus == 'pending') {
                $transaction->update(['status' => 'pending']);
            } else if (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                $transaction->update(['status' => 'failed']);
            }

            // Update callback data
            $transaction->update([
                'callback_data' => json_encode($payload),
                'processed_at' => now(),
                'channel' => $payload['payment_type'] ?? 'midtrans'
            ]);

            DB::commit();
            
            Log::info('Midtrans notification processed successfully', [
                'order_id' => $orderId,
                'old_status' => $transaction->getOriginal('status'),
                'new_status' => $transaction->status,
                'user_id' => $transaction->user_id,
                'amount' => $transaction->amount
            ]);

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans notification error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'payload' => $payload
            ]);
            
            return response()->json(['message' => 'Error processing notification'], 500);
        }
    }

    /**
     * Debug endpoint to check raw request
     */
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
     * Generate signature for testing (helper endpoint)
     */
    public function generateSignature(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'gross_amount' => 'required|string'
        ]);

        $orderId = $request->order_id;
        $statusCode = $request->status_code ?? '200';
        $grossAmount = $request->gross_amount;
        
        $hash = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        
        return response()->json([
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'server_key' => substr($this->serverKey, 0, 10) . '...',
            'signature_key' => $hash,
            'string_to_hash' => $orderId . $statusCode . $grossAmount . '[SERVER_KEY]',
            'raw_input' => $request->all()
        ]);
    }

    /**
     * Get transaction status
     */
    public function getStatus($transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'transaction' => [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'total_amount' => $transaction->total_amount,
                'created_at' => $transaction->created_at,
                'processed_at' => $transaction->processed_at
            ]
        ]);
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment($transaction)
    {
        $transaction->update(['status' => 'success']);
        
        // Add balance to user
        $user = $transaction->user;
        $user->increment('balance', $transaction->amount);
        
        Log::info('Top up successful', [
            'user_id' => $user->id,
            'amount' => $transaction->amount,
            'new_balance' => $user->balance
        ]);
    }

    /**
     * Calculate admin fee
     */
    private function calculateAdminFee($amount)
    {
        // Admin fee structure
        if ($amount <= 50000) {
            return 2500; // Rp 2.500 for amount <= 50k
        } else if ($amount <= 100000) {
            return 5000; // Rp 5.000 for amount <= 100k
        } else {
            return 7500; // Rp 7.500 for amount > 100k
        }
    }

    /**
     * Get Snap Token from Midtrans
     */
    private function getSnapToken($params)
    {
        $url = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception('Failed to get snap token: ' . $response);
        }

        $result = json_decode($response, true);
        return $result['token'];
    }

    /**
     * Verify Midtrans signature
     */
    private function verifySignature($payload)
    {
        // Check if required keys exist
        if (!isset($payload['order_id']) || !isset($payload['status_code']) || 
            !isset($payload['gross_amount']) || !isset($payload['signature_key'])) {
            Log::warning('Missing required fields in Midtrans payload', $payload);
            return false;
        }

        $orderId = $payload['order_id'];
        $statusCode = $payload['status_code'];
        $grossAmount = $payload['gross_amount'];
        $signatureKey = $payload['signature_key'];

        $stringToHash = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $hash = hash('sha512', $stringToHash);
        
        $isValid = $hash === $signatureKey;
        
        // Debug log
        Log::info('Signature verification debug', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'server_key_length' => strlen($this->serverKey),
            'server_key_prefix' => substr($this->serverKey, 0, 10),
            'string_to_hash' => $stringToHash,
            'calculated_hash' => $hash,
            'received_signature' => $signatureKey,
            'is_valid' => $isValid
        ]);
        
        if (!$isValid) {
            Log::warning('Invalid signature details', [
                'expected' => $hash,
                'received' => $signatureKey,
                'string_to_hash' => $stringToHash
            ]);
        }
        
        return $isValid;
    }
}
