@extends(backpack_view('blank'))

@section('header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">Manajemen Saldo User</h1>
        <p class="ms-2 ml-2 mb-0" id="datatable_info_stack" bp-section="page-subheading">Kelola saldo dan transaksi user</p>
    </section>
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total User</h5>
                            <h3 class="mb-0">{{ number_format($totalUsers) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="la la-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total Saldo</h5>
                            <h3 class="mb-0">Rp {{ number_format($totalBalance, 0, ',', '.') }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="la la-wallet fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total Top Up</h5>
                            <h3 class="mb-0">Rp {{ number_format($totalTopUp, 0, ',', '.') }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="la la-arrow-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total Pengeluaran</h5>
                            <h3 class="mb-0">Rp {{ number_format($totalSpending, 0, ',', '.') }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="la la-arrow-down fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Transaksi Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID Transaksi</th>
                                    <th>User</th>
                                    <th>Tipe</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td><small>{{ $transaction->transaction_id }}</small></td>
                                    <td>{{ $transaction->user->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-sm 
                                            @if($transaction->type === 'topup') badge-success
                                            @elseif($transaction->type === 'pulsa') badge-primary
                                            @elseif($transaction->type === 'pln') badge-warning
                                            @else badge-secondary
                                            @endif">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>Rp {{ number_format($transaction->total_amount, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-sm 
                                            @if($transaction->status === 'success') badge-success
                                            @elseif($transaction->status === 'pending') badge-warning
                                            @elseif($transaction->status === 'failed') badge-danger
                                            @else badge-info
                                            @endif">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                    <td><small>{{ $transaction->created_at->format('d/m/Y H:i') }}</small></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada transaksi</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Balance Users -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User dengan Saldo Tertinggi</h5>
                </div>
                <div class="card-body">
                    @forelse($topBalanceUsers as $user)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>{{ $user->name }}</strong><br>
                            <small class="text-muted">{{ $user->email }}</small>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-success">Rp {{ number_format($user->balance, 0, ',', '.') }}</span><br>
                        </div>
                    </div>
                    <hr>
                    @empty
                    <p class="text-center">Tidak ada user dengan saldo</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <!-- Monthly Stats -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistik Bulanan (6 Bulan Terakhir)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th>Total Transaksi</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthlyStats as $stat)
                                <tr>
                                    <td>{{ \Carbon\Carbon::createFromFormat('Y-m', $stat->month)->format('F Y') }}</td>
                                    <td>{{ number_format($stat->total_transactions) }}</td>
                                    <td>Rp {{ number_format($stat->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">Tidak ada data</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Balance Adjustment Modal -->
<div class="modal fade" id="adjustBalanceModal" tabindex="-1" role="dialog" aria-labelledby="adjustBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="z-index: 1060;">
        <div class="modal-content" style="position: relative; z-index: 1061;">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustBalanceModalLabel">Adjust Saldo User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="adjustBalanceForm" style="position: relative; z-index: 1;">
                <div class="modal-body" style="position: relative; z-index: 1;">
                    <input type="hidden" id="adjust_user_id" name="user_id">
                    
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="adjust_user_name" class="form-control" readonly style="position: relative; z-index: 1;">
                    </div>
                    
                    <div class="form-group">
                        <label>Saldo Saat Ini</label>
                        <input type="text" id="adjust_current_balance" class="form-control" readonly style="position: relative; z-index: 1;">
                    </div>
                    
                    <div class="form-group">
                        <label>Tipe Adjustment</label>
                        <select name="type" id="adjust_type" class="form-control" required style="position: relative; z-index: 1;">
                            <option value="add">Tambah Saldo</option>
                            <option value="subtract">Kurangi Saldo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="amount" id="adjust_amount" class="form-control" min="1000" step="1000" required style="position: relative; z-index: 1;">
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="notes" id="adjust_notes" class="form-control" rows="3" required placeholder="Alasan adjustment..." style="position: relative; z-index: 1;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="position: relative; z-index: 1;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="position: relative; z-index: 1;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="position: relative; z-index: 1;">Adjust Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('after_scripts')
<script>
function adjustBalance(userId, userName, currentBalance) {
    $('#adjust_user_id').val(userId);
    $('#adjust_user_name').val(userName);
    $('#adjust_current_balance').val('Rp ' + new Intl.NumberFormat('id-ID').format(currentBalance));
    
    // Reset form
    $('#adjust_type').val('add');
    $('#adjust_amount').val('');
    $('#adjust_notes').val('');
    
    // Show modal using Bootstrap 4
    $('#adjustBalanceModal').modal('show');
}

$('#adjustBalanceForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("admin.wallet.adjust-balance") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        type: 'success',
                        text: response.message
                    }).show();
                } else {
                    alert(response.message);
                }
                
                $('#adjustBalanceModal').modal('hide');
                location.reload();
            } else {
                if (typeof Noty !== 'undefined') {
                    new Noty({
                        type: 'error',
                        text: response.message
                    }).show();
                } else {
                    alert(response.message);
                }
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            const message = response && response.message ? response.message : 'Terjadi kesalahan';
            
            if (typeof Noty !== 'undefined') {
                new Noty({
                    type: 'error',
                    text: message
                }).show();
            } else {
                alert(message);
            }
        }
    });
});
</script>
@endsection
