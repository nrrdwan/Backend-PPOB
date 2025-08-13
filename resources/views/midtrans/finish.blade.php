<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Berhasil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .success { color: #28a745; }
        .container { max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✅ Pembayaran Berhasil!</h1>
        <p>Terima kasih! Top up saldo Anda telah berhasil diproses.</p>
        <p>Saldo akan segera ditambahkan ke akun Anda.</p>
        <button onclick="closeWindow()">Tutup</button>
    </div>
    
    <script>
        function closeWindow() {
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage('payment_success');
            } else {
                window.close();
            }
        }
        
        // Auto close after 3 seconds
        setTimeout(closeWindow, 3000);
    </script>
</body>
</html>
