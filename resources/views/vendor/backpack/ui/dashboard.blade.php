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

@section('content')
@endsection
