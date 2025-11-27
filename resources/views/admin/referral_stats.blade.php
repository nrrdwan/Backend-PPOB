<!-- File: resources/views/admin/referral_stats.blade.php -->

@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="la la-chart-line"></i> Statistik Referral</h2>
        </div>
    </div>

    <!-- Overall Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Referral</h5>
                    <h2>{{ number_format($totalReferrals) }}</h2>
                    <small>Pengguna yang direferensikan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Komisi Dibayar</h5>
                    <h2>Rp {{ number_format($totalCommissionPaid, 0, ',', '.') }}</h2>
                    <small>Total komisi yang dibayarkan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Referrer Aktif</h5>
                    <h2>{{ number_format($activeReferrers) }}</h2>
                    <small>User dengan referral > 0</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Rata-rata Referral</h5>
                    <h2>{{ number_format($avgReferralsPerUser, 1) }}</h2>
                    <small>Per referrer aktif</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Referrers -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="la la-trophy"></i> Top 10 Referrers</h4>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Kode Referral</th>
                                <th>Total Referral</th>
                                <th>Total Komisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topReferrers as $index => $referrer)
                            <tr>
                                <td><strong>{{ $index + 1 }}</strong></td>
                                <td>{{ $referrer->name }}</td>
                                <td>{{ $referrer->email }}</td>
                                <td><code>{{ $referrer->referral_code }}</code></td>
                                <td><span class="badge badge-primary">{{ $referrer->referral_count }}</span></td>
                                <td><strong class="text-success">Rp {{ number_format($referrer->referral_earnings, 0, ',', '.') }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Stats Chart -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i class="la la-chart-bar"></i> Statistik Bulanan (12 Bulan Terakhir)</h4>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4><i class="la la-chart-pie"></i> Distribusi Komisi</h4>
                </div>
                <div class="card-body">
                    <canvas id="commissionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="la la-clock"></i> 20 Transaksi Terakhir</h4>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Referrer</th>
                                <th>Referred User</th>
                                <th>Referral #</th>
                                <th>Komisi</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    {{ $transaction->referrer->name }}<br>
                                    <small class="text-muted">{{ $transaction->referrer->email }}</small>
                                </td>
                                <td>
                                    {{ $transaction->referred->name }}<br>
                                    <small class="text-muted">{{ $transaction->referred->email }}</small>
                                </td>
                                <td><span class="badge badge-info">#{{ $transaction->referral_number }}</span></td>
                                <td><strong class="text-success">Rp {{ number_format($transaction->commission_amount, 0, ',', '.') }}</strong></td>
                                <td>
                                    @if($transaction->status === 'paid')
                                        <span class="badge badge-success">Dibayar</span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-danger">Dibatalkan</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Stats Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($monthlyStats->pluck('month')->reverse()) !!},
        datasets: [{
            label: 'Total Referral',
            data: {!! json_encode($monthlyStats->pluck('total_referrals')->reverse()) !!},
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Total Komisi (Ribuan Rp)',
            data: {!! json_encode($monthlyStats->pluck('total_commission')->reverse()->map(fn($v) => $v / 1000)) !!},
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Commission Distribution Chart
const commissionCtx = document.getElementById('commissionChart').getContext('2d');
new Chart(commissionCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(array_keys($commissionDistribution)) !!},
        datasets: [{
            data: {!! json_encode(array_values($commissionDistribution)) !!},
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            }
        }
    }
});
</script>
@endpush
@endsection