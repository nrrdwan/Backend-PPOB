@extends(backpack_view('blank'))

@php
$widgets['before_content'][] = [
    'type'         => 'jumbotron',
    'heading'      => 'Selamat Datang di Merah Putih Pay',
    'content'      => 'Dashboard untuk mengelola sistem Payment Point Online Banking Anda.',
    'button_link'  => backpack_url('product'),
    'button_text'  => 'Kelola Produk',
    'heading_class'=> 'text-white',
    'content_class'=> 'text-white-50',
    'button_class' => 'btn btn-light',
    'wrapper'      => ['class' => 'mb-4'],
];
@endphp

@section('content')
{{-- Main Stats Cards --}}
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
            <div class="card-icon bg-primary"><i class="fas fa-users"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Total Users</h4></div>
                <div class="card-body">{{ number_format($total_users ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
            <div class="card-icon bg-success"><i class="fas fa-box"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Produk Aktif</h4></div>
                <div class="card-body">{{ number_format($active_products ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
            <div class="card-icon bg-warning"><i class="fas fa-exchange-alt"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Total Transaksi</h4></div>
                <div class="card-body">{{ number_format($total_transactions ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
            <div class="card-icon bg-info"><i class="fas fa-money-bill-wave"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Total Revenue</h4></div>
                <div class="card-body">Rp {{ number_format($total_revenue ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Secondary Stats --}}
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon"><i class="fas fa-user-check text-success"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>User Aktif (30 hari)</h4></div>
                <div class="card-body">{{ number_format($active_users ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon"><i class="fas fa-calendar-day text-primary"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Transaksi Hari Ini</h4></div>
                <div class="card-body">{{ number_format($today_transactions ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon"><i class="fas fa-clock text-warning"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Transaksi Pending</h4></div>
                <div class="card-body">{{ number_format($pending_transactions ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-2">
            <div class="card-icon"><i class="fas fa-coins text-info"></i></div>
            <div class="card-wrap">
                <div class="card-header"><h4>Revenue Bulan Ini</h4></div>
                <div class="card-body">Rp {{ number_format($this_month_revenue ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Charts & Activity --}}
<div class="row">
    <div class="col-lg-8 col-md-12 col-12 col-sm-12">
        <div class="card">
            <div class="card-header"><h4>Statistik Transaksi 6 Bulan Terakhir</h4></div>
            <div class="card-body">
                {{-- Tanam data ke data-attributes agar JS-nya “bersih” di editor --}}
                <canvas
                    id="transactionChart"
                    height="120"
                    aria-label="Grafik transaksi"
                    role="img"
                    data-labels='@json($monthly_stats["labels"] ?? [])'
                    data-series='@json($monthly_stats["data"] ?? [])'
                ></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12 col-12 col-sm-12">
        <div class="card card-activity">
            <div class="card-header"><h4>Aktivitas Terkini</h4></div>
            <div class="card-body">
                {{-- Maks 3 item + jarak antar item lebih lega --}}
                <ul class="list-unstyled list-unstyled-border recent-activity">
                    @forelse ($recent_transactions->take(3) as $transaction)
                        <li class="media">
                            <img class="mr-3 rounded-circle" width="50"
                                 src="https://ui-avatars.com/api/?name={{ urlencode($transaction->user->name ?? 'User') }}&background=6777ef&color=fff"
                                 alt="Avatar {{ $transaction->user->name ?? 'User' }}">
                            <div class="media-body">
                                <div class="float-right text-primary">{{ $transaction->created_at->diffForHumans() }}</div>
                                <div class="media-title">{{ $transaction->user->name ?? 'User' }}</div>
                                <span class="text-small text-muted">
                                    {{ $transaction->product->name ?? $transaction->type }} -
                                    @php
                                        $badge = $transaction->status === 'success'
                                            ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'danger');
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">{{ ucfirst($transaction->status) }}</span>
                                </span>
                            </div>
                        </li>
                    @empty
                        <li class="media">
                            <img class="mr-3 rounded-circle" width="50"
                                 src="https://ui-avatars.com/api/?name=System&background=6777ef&color=fff"
                                 alt="Avatar Sistem">
                            <div class="media-body">
                                <div class="float-right text-primary">Sekarang</div>
                                <div class="media-title">Sistem PPOB</div>
                                <span class="text-small text-muted">Dashboard berhasil dimuat, belum ada transaksi</span>
                            </div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- PPOB Services Overview --}}
<div class="row">
    <div class="col-lg-8 col-md-12 col-12 col-sm-12">
        <div class="card">
            <div class="card-header"><h4>Layanan PPOB Tersedia</h4></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-mobile-alt fa-2x text-primary"></i></div>
                            <h6>Pulsa</h6>
                            <small class="text-muted">{{ $product_stats['pulsa'] ?? 0 }} produk</small>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-wifi fa-2x text-success"></i></div>
                            <h6>Paket Data</h6>
                            <small class="text-muted">{{ $product_stats['data'] ?? 0 }} produk</small>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-bolt fa-2x text-warning"></i></div>
                            <h6>PLN</h6>
                            <small class="text-muted">{{ $product_stats['pln'] ?? 0 }} produk</small>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-gamepad fa-2x text-danger"></i></div>
                            <h6>Voucher Game</h6>
                            <small class="text-muted">{{ $product_stats['game'] ?? 0 }} produk</small>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-wallet fa-2x text-info"></i></div>
                            <h6>E-Money</h6>
                            <small class="text-muted">{{ $product_stats['emoney'] ?? 0 }} produk</small>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-6 col-6 mb-3">
                        <div class="text-center service-item">
                            <div class="mb-2"><i class="fas fa-ellipsis-h fa-2x text-secondary"></i></div>
                            <h6>Lainnya</h6>
                            <small class="text-muted">{{ $product_stats['other'] ?? 0 }} produk</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-12 col-12 col-sm-12">
        <div class="card">
            <div class="card-header"><h4>Quick Actions</h4></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ backpack_url('product') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-box mr-2"></i> Kelola Produk
                    </a>
                    <a href="{{ backpack_url('transaction') }}" class="btn btn-success btn-lg">
                        <i class="fas fa-exchange-alt mr-2"></i> Lihat Transaksi
                    </a>
                    <a href="{{ backpack_url('user') }}" class="btn btn-info btn-lg">
                        <i class="fas fa-users mr-2"></i> Kelola User
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('transactionChart');
    if (!el) return;

    // Ambil data dari data-attributes (hindari Blade directive di JS)
    const labels = JSON.parse(el.dataset.labels || '[]');
    const series = JSON.parse(el.dataset.series || '[]');

    const ctx = el.getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Transaksi Bulanan',
                data: series,
                borderColor: '#6777ef',
                backgroundColor: 'rgba(103, 119, 239, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#6777ef',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Grafik Transaksi 6 Bulan Terakhir' },
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f1f1' } },
                x: { grid: { color: '#f1f1f1' } }
            }
        }
    });
});
</script>
@endpush

@push('after_styles')
<style>
    /* Jumbotron */
    .content-header .jumbotron{
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color:#fff;border-radius:15px;margin-bottom:30px;
    }
    .content-header .jumbotron h1{color:#fff!important;font-weight:700;text-shadow:0 2px 4px rgba(0,0,0,.3)}
    .content-header .jumbotron p{color:rgba(255,255,255,.9)!important;font-size:1.1rem}
    .content-header .jumbotron .btn{background:rgba(255,255,255,.2);border:2px solid rgba(255,255,255,.3);color:#fff;font-weight:600;transition:all .3s ease}
    .content-header .jumbotron .btn:hover{background:rgba(255,255,255,.3);border-color:rgba(255,255,255,.5);transform:translateY(-2px)}

    /* Cards */
    .card,.card-statistic-1,.card-statistic-2{background:#fff;border-radius:15px;border:none;box-shadow:0 2px 10px rgba(0,0,0,.08);transition:transform .3s ease}
    .card:hover,.card-statistic-1:hover,.card-statistic-2:hover{transform:translateY(-5px);box-shadow:0 5px 20px rgba(0,0,0,.15)}
    .card-statistic-1{padding:20px}
    .card-statistic-1 .card-icon{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;float:left;margin-right:15px}
    .card-statistic-2 .card-icon{font-size:30px;float:left;margin-right:15px;margin-top:10px}
    .card-wrap{overflow:hidden}
    .card-header{background:transparent;border-bottom:1px solid #eee;font-weight:600}
    .card-statistic-1 .card-header h4,.card-statistic-2 .card-header h4{font-size:14px;font-weight:600;color:#6c757d;margin:0}
    .card-statistic-1 .card-body,.card-statistic-2 .card-body{font-size:24px;font-weight:700;color:#495057;margin-top:5px}

    /* List w/ border */
    .list-unstyled-border li{border-bottom:1px solid #eee;padding-bottom:15px;margin-bottom:15px}
    .list-unstyled-border li:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}

    /* Spacing antara Aktivitas & card di bawahnya */
    .card-activity{margin-bottom:20px}

    /* Spacing antar item aktivitas (lebih lega) */
    .recent-activity li{border-bottom:1px solid #eee;margin-bottom:20px;padding-bottom:20px}
    .recent-activity li:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}

    /* Services */
    .service-item{padding:15px;border-radius:10px;transition:all .3s ease}
    .service-item:hover{background:#f8f9fa;transform:translateY(-3px)}

    /* Utils */
    .btn-lg{padding:12px 20px;font-weight:600;border-radius:10px;margin-bottom:10px}
    .d-grid{display:grid}.gap-2{gap:.5rem}
</style>
@endpush
