<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CoreMidtransController extends Controller
{
    private $serverKey;
    private $isProduction;
    
    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->isProduction = config('services.midtrans.is_production');
        
        Log::info('🔧 [MidtransCore] Controller initialized', [
            'server_key_prefix' => substr($this->serverKey, 0, 10) . '...',
            'is_production' => $this->isProduction
        ]);
    }

    /**
     * Create Transaction with Core API
     */
    public function createTransaction(Request $request)
    {
        Log::info('🔵 [MidtransCore] createTransaction called', ['request' => $request->all()]);

        $request->validate([
            'amount'  => 'required|numeric|min:10000|max:10000000',
            'payment_method'  => 'required|string',
            'bank'    => 'nullable|string',
            'wallet'  => 'nullable|string',
            'store'   => 'nullable|string',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $amount = $request->amount;
        $adminFee = $this->calculateAdminFee($amount);
        $totalAmount = $amount + $adminFee;
        $orderId = 'TOPUP-' . time() . '-' . $user->id . '-' . Str::random(6);

        if ($this->isMockMode($amount)) {
            return $this->createMockTransaction($user, $amount, $adminFee, $totalAmount);
        }

        $transaction = Transaction::create([
            'transaction_id' => $orderId,
            'user_id'        => $user->id,
            'type'           => 'topup',
            'amount'         => $amount,
            'admin_fee'      => $adminFee,
            'total_amount'   => $totalAmount,
            'status'         => 'pending',
            'channel'        => 'midtrans_core|' . $request->payment_method
        ]);

        $params = [
            "transaction_details" => [
                "order_id" => $orderId,
                "gross_amount" => $totalAmount
            ],
            "customer_details" => [
                "first_name" => $user->name,
                "email" => $user->email,
                "phone" => $user->phone ?? "081234567890"
            ],
            "item_details" => [
                [
                    "id" => "topup",
                    "price" => $amount,
                    "quantity" => 1,
                    "name" => "Top Up Saldo"
                ],
                [
                    "id" => "admin_fee",
                    "price" => $adminFee,
                    "quantity" => 1,
                    "name" => "Biaya Admin"
                ]
            ]
        ];

        switch (strtoupper($request->payment_method)) {
            case 'VA':
                $params['payment_type'] = 'bank_transfer';
                $params['bank_transfer'] = [
                    'bank' => strtolower($request->bank ?? 'bca')
                ];
                break;

            case 'MANDIRI':
                $params['payment_type'] = 'echannel';
                $params['echannel'] = [
                    "bill_info1" => "Top Up",
                    "bill_info2" => $user->name
                ];
                break;

            case 'EWALLET':
                $wallet = strtolower($request->wallet ?? 'gopay');
                $params['payment_type'] = $wallet;
                $params[$wallet] = [
                    "enable_callback" => true,
                    "callback_url" => config('app.url') . '/midtrans/finish'
                ];
                break;

            case 'QRIS':
                $params['payment_type'] = 'qris';
                break;

            case 'CSTORE':
                $params['payment_type'] = 'cstore';
                $params['cstore'] = [
                    "store" => strtolower($request->store ?? 'alfamart'),
                    "message" => "Pembayaran Top Up"
                ];
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported payment method'
                ], 400);
        }

        try {
            $result = $this->chargeCoreApi($params);
            $transaction->update(['reference_id' => $result['transaction_id'] ?? null]);

            return response()->json([
                'success' => true,
                'message' => 'Core transaction created successfully',
                'data'    => [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'total_amount' => $totalAmount,
                    'payment_details' => $this->extractPaymentDetails($result),
                    'midtrans' => $result
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('🔴 [MidtransCore] createTransaction exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $transaction->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran Core API',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getStatus($orderId)
    {
        $transaction = Transaction::where('transaction_id', $orderId)->first();
        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $url = $this->isProduction
            ? "https://api.midtrans.com/v2/{$orderId}/status"
            : "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

        $result = $this->doRequest('GET', $url);

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
            'midtrans' => $result,
            'payment_details' => $this->extractPaymentDetails($result)
        ]);
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
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

    private function extractPaymentDetails($midtransData)
    {
        $paymentDetails = [];
        if (isset($midtransData['payment_type'])) {
            switch ($midtransData['payment_type']) {
                case 'bank_transfer':
                    $paymentDetails['va_numbers'] = $midtransData['va_numbers'] ?? [];
                    $paymentDetails['permata_va_number'] = $midtransData['permata_va_number'] ?? null;
                    break;
                case 'echannel':
                    $paymentDetails['bill_key'] = $midtransData['bill_key'] ?? null;
                    $paymentDetails['biller_code'] = $midtransData['biller_code'] ?? null;
                    break;
                case 'qris':
                    $paymentDetails['qr_string'] = $midtransData['qr_string'] ?? null;
                    break;
                case 'cstore':
                    $paymentDetails['payment_code'] = $midtransData['payment_code'] ?? null;
                    $paymentDetails['store'] = $midtransData['store'] ?? null;
                    break;
                case 'gopay':
                case 'shopeepay':
                case 'dana':
                case 'linkaja':
                case 'ovo':
                    $paymentDetails['actions'] = $midtransData['actions'] ?? [];
                    break;
            }
        }
        if (isset($midtransData['instructions'])) {
            $paymentDetails['instructions'] = $midtransData['instructions'];
        }
        return $paymentDetails;
    }

    private function chargeCoreApi($params)
    {
        $url = $this->isProduction
            ? 'https://api.midtrans.com/v2/charge'
            : 'https://api.sandbox.midtrans.com/v2/charge';

        Log::info('📤 [MidtransCore] Sending charge request', [
            'url' => $url,
            'params' => $params
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        Log::info('📥 [MidtransCore] Response', [
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $curlError
        ]);

        if ($curlError) {
            throw new \Exception('CURL Error: ' . $curlError);
        }
        if ($httpCode !== 201) {
            throw new \Exception('Midtrans Core API returned HTTP ' . $httpCode . ': ' . $response);
        }

        return json_decode($response, true);
    }

    private function doRequest($method, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->serverKey . ':')
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) throw new \Exception('CURL Error: ' . $curlError);
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("Midtrans API HTTP {$httpCode}: {$response}");
        }

        return json_decode($response, true);
    }

    private function isMockMode($amount)
    {
        $mockAmounts = [10001,10002,10003];
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
            'channel' => 'mock_core',
            'reference_id' => 'mock-core-' . Str::random(8),
            'processed_at' => now()
        ]);
        $user->increment('balance', $amount);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Mock core payment successful',
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'payment_details' => [
                    'va_numbers' => [
                        ['bank' => 'mockbank', 'va_number' => '1234567890']
                    ]
                ]
            ]
        ]);
    }

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
}