@extends(backpack_view('blank'))

@php
    $widgets['before_content'][] = [
        'type' => 'div',
        'class' => 'row mb-4',
        'content' => [
            [
                'type' => 'progress',
                'class' => 'card border-0 text-white bg-primary',
                'value' => $total_users ?? 0,
                'description' => 'Total Users',
                'progress' => false,
                'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
            ],
            [
                'type' => 'progress',
                'class' => 'card border-0 text-white bg-success',
                'value' => $total_transactions ?? 0,
                'description' => 'Total Transactions',
                'progress' => false,
                'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
            ],
            [
                'type' => 'progress',
                'class' => 'card border-0 text-white bg-warning',
                'value' => 'Rp ' . number_format($total_balance ?? 0, 0, ',', '.'),
                'description' => 'Total User Balance',
                'progress' => false,
                'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
            ],
            [
                'type' => 'progress',
                'class' => 'card border-0 text-white bg-info',
                'value' => 'Rp ' . number_format($total_revenue ?? 0, 0, ',', '.'),
                'description' => 'Total Revenue',
                'progress' => false,
                'wrapper' => ['class' => 'col-sm-6 col-lg-3'],
            ],
        ]
    ];

    $widgets['before_content'][] = [
        'type' => 'div',
        'class' => 'row',
        'content' => [
            [
                'type' => 'card',
                'wrapper' => ['class' => 'col-md-6'],
                'class' => 'card',
                'header' => [
                    'title' => 'Transaction Stats',
                ],
                'body' => [
                    '<div class="row">
                        <div class="col-6 text-center">
                            <h4 class="text-success">' . ($success_transactions ?? 0) . '</h4>
                            <small>Success</small>
                        </div>
                        <div class="col-6 text-center">
                            <h4 class="text-warning">' . ($pending_transactions ?? 0) . '</h4>
                            <small>Pending</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6 text-center">
                            <h4 class="text-info">' . ($today_transactions ?? 0) . '</h4>
                            <small>Today</small>
                        </div>
                        <div class="col-6 text-center">
                            <h4 class="text-primary">' . ($active_products ?? 0) . '</h4>
                            <small>Active Products</small>
                        </div>
                    </div>'
                ]
            ],
            [
                'type' => 'card',
                'wrapper' => ['class' => 'col-md-6'],
                'class' => 'card',
                'header' => [
                    'title' => 'Wallet Stats',
                ],
                'body' => [
                    '<div class="mb-3">
                        <label class="text-muted">Total Top-up</label>
                        <h5 class="text-success">Rp ' . number_format($total_topup ?? 0, 0, ',', '.') . '</h5>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Total Spending</label>
                        <h5 class="text-danger">Rp ' . number_format($total_spending ?? 0, 0, ',', '.') . '</h5>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Active Users (30 days)</label>
                        <h5 class="text-info">' . ($active_users ?? 0) . '</h5>
                    </div>'
                ]
            ]
        ]
    ];

    if(!empty($recent_transactions) && $recent_transactions->count() > 0) {
        $widgets['before_content'][] = [
            'type' => 'card',
            'class' => 'card',
            'header' => [
                'title' => 'Recent Transactions',
            ],
            'body' => [
                '<div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            ' . $recent_transactions->map(function($t) {
                                $statusClass = $t->status == 'success' ? 'success' : ($t->status == 'pending' ? 'warning' : 'danger');
                                return '<tr>
                                    <td>' . ($t->user->name ?? 'N/A') . '</td>
                                    <td>' . ($t->product->name ?? 'N/A') . '</td>
                                    <td><span class="badge bg-info">' . ucfirst($t->type) . '</span></td>
                                    <td>Rp ' . number_format($t->total_amount, 0, ',', '.') . '</td>
                                    <td><span class="badge bg-' . $statusClass . '">' . ucfirst($t->status) . '</span></td>
                                    <td>' . $t->created_at->format('d/m/Y H:i') . '</td>
                                </tr>';
                            })->join('') . '
                        </tbody>
                    </table>
                </div>'
            ]
        ];
    }
@endphp

@push('after_styles')
<style>
    /* Progress Cards - Merah Putih Theme */
    .bg-primary { background-color: #FF0000 !important; }
    .bg-success { background-color: #CC0000 !important; }
    .bg-warning { background-color: #FFC107 !important; }
    .bg-info { background-color: #F44336 !important; }
    
    /* Card Stats Colors */
    .text-success { color: #CC0000 !important; }
    .text-primary { color: #FF0000 !important; }
    .text-info { color: #F44336 !important; }
    .text-warning { color: #FFC107 !important; }
    
    /* Badge Colors */
    .badge.bg-success { background-color: #CC0000 !important; }
    .badge.bg-danger { background-color: #FF0000 !important; }
    .badge.bg-warning { background-color: #FFC107 !important; }
    .badge.bg-info { background-color: #F44336 !important; }
    
    /* Card Styling */
    .card {
        border-radius: 15px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,.08);
        transition: transform .3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,.15);
    }
</style>
@endpush

@push('after_scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart untuk transaksi (jika ada canvas dengan id transactionChart)
    const el = document.getElementById('transactionChart');
    if (el) {
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
                    borderColor: '#FF0000',
                    backgroundColor: 'rgba(255,0,0,0.1)',
                    pointBackgroundColor: '#FF0000',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { 
                responsive: true,
                plugins: {
                    title: { 
                        display: true, 
                        text: 'Grafik Transaksi Bulanan',
                        color: '#FF0000'
                    },
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f1f1' },
                        ticks: { color: '#666' }
                    },
                    x: { 
                        grid: { color: '#f1f1f1' },
                        ticks: { color: '#666' }
                    }
                }
            }
        });
    }
});
</script>
@endpush

@section('content')
@endsection
