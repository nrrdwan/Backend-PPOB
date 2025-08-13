<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Gagal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #dc3545; }
        .container { max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error">❌ Pembayaran Gagal</h1>
        <p>Maaf, pembayaran Anda tidak dapat diproses.</p>
        <p>Silakan coba lagi atau hubungi customer service.</p>
        <button onclick="closeWindow()">Tutup</button>
    </div>
    
    <script>
        function closeWindow() {
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage('payment_error');
            } else {
                window.close();
            }
        }
    </script>
</body>
</html>
