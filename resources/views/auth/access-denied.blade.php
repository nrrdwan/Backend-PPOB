<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - PPOB Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .access-denied-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            margin: 20px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            border: none;
        }
        .card-body {
            padding: 2rem;
            text-align: center;
        }
        .icon-warning {
            font-size: 4rem;
            color: #ff6b6b;
            margin-bottom: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .role-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            border-left: 4px solid #ff6b6b;
        }
    </style>
</head>
<body>
    <div class="access-denied-card">
        <div class="card-header">
            <i class="fas fa-shield-alt fa-3x mb-3"></i>
            <h2 class="mb-0">PPOB Admin Panel</h2>
        </div>
        <div class="card-body">
            <i class="fas fa-exclamation-triangle icon-warning"></i>
            <h3 class="text-danger mb-3">Akses Ditolak!</h3>
            
            @if(isset($userRole))
            <div class="role-info">
                <strong>Role Anda:</strong> {{ $userRole }}<br>
                <strong>Status:</strong> <span class="text-danger">Tidak Diizinkan</span>
            </div>
            @endif
            
            <p class="lead mb-4">
                Hanya pengguna dengan role <strong class="text-primary">Admin</strong> 
                yang dapat mengakses panel admin ini.
            </p>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong><br>
                Role yang diizinkan: <strong>Admin</strong><br>
                Role yang tidak diizinkan: Agen PPOB, User Biasa, dan lainnya
            </div>
            
            <div class="d-grid gap-2">
                <a href="{{ route('backpack.auth.login') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Kembali ke Login
                </a>
                <a href="/" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>
                    Ke Halaman Utama
                </a>
            </div>
            
            <hr class="my-4">
            
            <small class="text-muted">
                <i class="fas fa-question-circle me-1"></i>
                Butuh akses Admin? Hubungi administrator sistem.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
