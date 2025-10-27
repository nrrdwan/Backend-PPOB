<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Diterima - PPOB Modipay</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
        }
        .success-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        .success-icon svg {
            width: 80px;
            height: 80px;
        }
        .amount {
            text-align: center;
            margin: 20px 0;
        }
        .amount-value {
            font-size: 36px;
            font-weight: bold;
            color: #10b981;
            margin: 10px 0;
        }
        .amount-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-box {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            color: #111827;
            font-weight: 600;
            font-size: 14px;
            text-align: right;
        }
        .message {
            text-align: center;
            color: #4b5563;
            margin: 20px 0;
            line-height: 1.6;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>💰 Transfer Diterima</h1>
        </div>
        
        <div class="content">
            <div class="success-icon">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="12" cy="12" r="10" fill="#10b981"/>
                    <path d="M8 12L11 15L16 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <p class="message">
                Halo <strong>{{ $recipientName }}</strong>,<br>
                Anda telah menerima transfer saldo dari <strong>{{ $senderName }}</strong>
            </p>

            <div class="amount">
                <div class="amount-label">Jumlah Transfer</div>
                <div class="amount-value">{{ $formattedAmount }}</div>
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Pengirim</span>
                    <span class="info-value">{{ $senderName }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. HP Pengirim</span>
                    <span class="info-value">{{ $senderPhone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal & Waktu</span>
                    <span class="info-value">{{ $transactionDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">No. Referensi</span>
                    <span class="info-value">{{ $reference }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Saldo Terkini</span>
                    <span class="info-value">{{ $currentBalance }}</span>
                </div>
            </div>

            <p class="message">
                Dana sudah masuk ke saldo Modipay Anda dan siap digunakan.
            </p>
        </div>

        <div class="footer">
            <p>
                Email ini dikirim secara otomatis oleh sistem Modipay.<br>
                Jika ada pertanyaan, hubungi <a href="mailto:support@modipay.com">support@modipay.com</a>
            </p>
            <p style="margin-top: 10px;">
                © {{ date('Y') }} Modipay. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>