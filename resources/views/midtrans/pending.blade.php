<!DOCTYPE html>
<html>
<head>
    <title>Pembayaran Pending</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .pending { color: #ffc107; }
        .container { max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="pending">⏳ Pembayaran Tertunda</h1>
        <p>Pembayaran Anda sedang diproses.</p>
        <p>Silakan tunggu konfirmasi atau cek status di aplikasi.</p>
        <button onclick="closeWindow()">Tutup</button>
    </div>
    
    <script>
        function closeWindow() {
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage('payment_pending');
            } else {
                window.close();
            }
        }
    </script>
</body>
</html>
