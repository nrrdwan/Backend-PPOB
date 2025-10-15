<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected $serverKey;
    protected $isProduction;
    protected $snapBaseUrl;
    protected $coreBaseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->isProduction = config('services.midtrans.is_production', false);

        $this->snapBaseUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';

        $this->coreBaseUrl = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createCoreTransaction(array $params): array
    {
        $url = "{$this->coreBaseUrl}/charge";
        Log::info('📤 [MidtransService] Creating Core Transaction', ['url' => $url, 'params' => $params]);

        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->post($url, $params);

        if (!$response->successful()) {
            Log::error('🔴 [MidtransService] Core Transaction failed', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Failed to create Core Transaction: ' . $response->body());
        }

        $data = $response->json();
        Log::info('✅ [MidtransService] Core Transaction created', ['response' => $data]);
        return $data;
    }

    public function createManualTransfer($amount, $deviceName = 'Unknown Device')
    {
        // Generate unique transaction ID dan kode unik
        $transactionId = 'TF-' . strtoupper(uniqid());
        $uniqueCode = 'DEP' . random_int(100000, 999999);

        // Simpan ke database (jika perlu, tergantung struktur tabel kamu)
        $transaction = \App\Models\Transaction::create([
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'status' => 'pending',
            'channel' => 'manual_transfer',
            'provider_response' => json_encode([]),
        ]);

        // Data rekening tujuan (bisa disesuaikan)
        $bankInfo = [
            'bank_name' => 'Bank BCA',
            'account_number' => '010001002976560',
            'account_name' => 'PT MODI TEKNO SOLUSINDO',
        ];

        return [
            'transaction_id' => $transactionId,
            'transfer_code' => $uniqueCode,
            'amount' => $amount,
            'admin_fee' => 2500,
            'total_amount' => $amount + 2500,
            'bank_name' => $bankInfo['bank_name'],
            'account_number' => $bankInfo['account_number'],
            'account_name' => $bankInfo['account_name'],
            'status' => 'pending',
        ];
    }

    public function createBankTransfer(array $transactionData, string $bankType): array
    {
        // 🔹 Gunakan echannel khusus untuk Mandiri
        if ($bankType === 'mandiri') {
            $params = [
                'payment_type' => 'echannel',
                'transaction_details' => [
                    'order_id' => $transactionData['order_id'],
                    'gross_amount' => $transactionData['gross_amount'],
                ],
                'customer_details' => $transactionData['customer_details'] ?? [],
                'item_details' => $transactionData['item_details'] ?? [],
                'echannel' => [
                    'bill_info1' => 'Payment:',
                    'bill_info2' => 'Top Up Saldo PPOB',
                ],
            ];

            Log::info('🏦 [MidtransService] Creating Mandiri E-Channel', ['params' => $params]);
        } else {
            // 🔹 Bank lain (BCA, BNI, BRI, Permata, etc)
            $params = [
                'payment_type' => 'bank_transfer',
                'transaction_details' => [
                    'order_id' => $transactionData['order_id'],
                    'gross_amount' => $transactionData['gross_amount'],
                ],
                'customer_details' => $transactionData['customer_details'] ?? [],
                'item_details' => $transactionData['item_details'] ?? [],
                'bank_transfer' => [
                    'bank' => $bankType,
                ],
            ];

            if (in_array($bankType, ['bca', 'bni', 'bri'])) {
                $params['bank_transfer']['va_number'] = ''; // biarkan Midtrans generate otomatis
            }
        }

        return $this->createCoreTransaction($params);
    }

    /**
     * 🚀 Membuat Snap Token (untuk transaksi Snap UI)
     */
    public function createSnapToken(array $params): string
    {
        $url = "{$this->snapBaseUrl}/transactions";
        Log::info('📤 [MidtransService] Creating Snap Token', ['url' => $url, 'params' => $params]);

        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->post($url, $params);

        if (!$response->successful()) {
            Log::error('🔴 [MidtransService] Snap Token failed', ['body' => $response->body()]);
            throw new \Exception('Failed to create Snap Token: ' . $response->body());
        }

        $data = $response->json();
        if (!isset($data['token'])) {
            throw new \Exception('Invalid Midtrans response: token missing');
        }

        Log::info('✅ [MidtransService] Snap Token created', ['token' => $data['token']]);
        return $data['token'];
    }

    public function getPaymentInstructions(array $midtransData): array
    {
        $paymentType = $midtransData['payment_type'] ?? null;
        $instructions = [];

        switch ($paymentType) {
            case 'bank_transfer':
                if (isset($midtransData['va_numbers'][0])) {
                    $va = $midtransData['va_numbers'][0];
                    $instructions = [
                        'type' => 'va',
                        'va_number' => $va['va_number'],
                        'bank' => $va['bank'],
                        'instructions' => $midtransData['instructions'] ?? []
                    ];
                }
                break;

            case 'gopay':
            case 'shopeepay':
            case 'dana':
            case 'linkaja':
                $instructions = [
                    'type' => 'ewallet',
                    'actions' => $midtransData['actions'] ?? [],
                    'deeplink' => $this->findDeeplink($midtransData['actions'] ?? [])
                ];
                break;

            case 'qris':
                $instructions = [
                    'type' => 'qris',
                    'qr_string' => $midtransData['qr_string'] ?? null,
                    'actions' => $midtransData['actions'] ?? []
                ];
                break;

            case 'cstore':
                $instructions = [
                    'type' => 'counter',
                    'payment_code' => $midtransData['payment_code'] ?? null,
                    'store' => $midtransData['store'] ?? '',
                    'instructions' => $midtransData['instructions'] ?? []
                ];
                break;
        }

        return $instructions;
    }

    private function findDeeplink(array $actions): string
    {
        foreach ($actions as $action) {
            if ($action['name'] === 'deeplink-redirect') {
                return $action['url'];
            }
        }
        return '';
    }

    /**
     * 🔍 Ambil status transaksi berdasarkan order_id
     */
    public function getStatus(string $orderId): array
    {
        $url = "{$this->coreBaseUrl}/{$orderId}/status";
        Log::info('🔎 [MidtransService] Fetching status', ['url' => $url]);

        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->get($url);

        if (!$response->successful()) {
            Log::error('🔴 [MidtransService] Get status failed', [
                'code' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Midtrans status fetch failed ({$response->status()})");
        }

        return $response->json();
    }

    /**
     * ❌ Batalkan transaksi (cancel)
     */
    public function cancelTransaction(string $orderId): array
    {
        $url = "{$this->coreBaseUrl}/{$orderId}/cancel";
        Log::info('⚠️ [MidtransService] Cancel transaction', ['url' => $url]);

        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->post($url);

        if (!$response->successful()) {
            Log::error('🔴 [MidtransService] Cancel failed', [
                'code' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Failed to cancel transaction: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * 🧩 Helper untuk mengekstrak detail pembayaran dari response Midtrans
     */
    public function extractPaymentDetails(array $midtransData): array
    {
        $paymentType = $midtransData['payment_type'] ?? null;
        $details = [
            'payment_type' => $paymentType,
        ];

        switch ($paymentType) {
            case 'bank_transfer':
                $details['va_numbers'] = $midtransData['va_numbers'] ?? [];
                $details['permata_va_number'] = $midtransData['permata_va_number'] ?? null;
                break;

            case 'echannel': // 🔹 Mandiri VA (bill payment)
                $details['bill_key'] = $midtransData['echannel']['bill_key'] ?? null;
                $details['biller_code'] = $midtransData['echannel']['biller_code'] ?? null;
                break;

            case 'qris':
                $details['qr_string'] = $midtransData['qr_string'] ?? null;
                $details['actions'] = $midtransData['actions'] ?? [];
                break;

            case 'gopay':
            case 'shopeepay':
            case 'dana':
            case 'linkaja':
                $details['actions'] = $midtransData['actions'] ?? [];
                break;
        }

        if (isset($midtransData['instructions'])) {
            $details['instructions'] = $midtransData['instructions'];
        }

        // 🔹 Log untuk debugging
        Log::info('🟢 [extractPaymentDetails] Parsed Payment Details:', $details);

        return $details;
    }

    private function verifySignature($payload)
    {
        if (!isset($payload['order_id'],$payload['status_code'],$payload['gross_amount'],$payload['signature_key'])) {
            return false;
        }
        $stringToHash = $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey;
        return hash('sha512',$stringToHash) === $payload['signature_key'];
    }
}