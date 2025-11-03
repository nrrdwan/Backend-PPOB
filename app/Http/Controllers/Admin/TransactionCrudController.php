<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\TransactionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class TransactionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class TransactionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Transaction::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/transaction');
        CRUD::setEntityNameStrings('transaksi', 'transaksi');
        CRUD::orderBy('created_at', 'DESC');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Tanggal & Waktu',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->created_at ? $entry->created_at->format('d M Y, H:i') : '-';
            },
            'orderable' => true
        ]);
        CRUD::addColumn([
            'name' => 'transaction_id',
            'label' => 'Order ID',
            'type' => 'text'
        ]);
        CRUD::addColumn([
            'name' => 'type',
            'label' => 'Jenis Transaksi',
            'type' => 'closure',
            'function' => function($entry) {
                $types = [
                    'topup' => 'Top Up',
                    'pulsa' => 'Pulsa',
                    'pln' => 'PLN',
                    'pdam' => 'PDAM',
                    'game' => 'Game',
                    'emoney' => 'E-Money',
                    'other' => 'Pembayaran'
                ];
                return $types[$entry->type] ?? 'Pembayaran';
            }
        ]);
        CRUD::addColumn([
            'name' => 'channel',
            'label' => 'Channel',
            'type' => 'closure',
            'function' => function($entry) {
                $methods = [
                    'dana' => 'DANA',
                    'ovo' => 'OVO',
                    'gopay' => 'GOPAY',
                    'qris' => 'QRIS',
                    'bank_transfer' => 'Bank Transfer',
                    'va_bca' => 'VA BCA',
                    'va_bri' => 'VA BRI',
                    'va_bni' => 'VA BNI',
                    'va_mandiri' => 'VA Mandiri',
                    'shopeepay' => 'ShopeePay',
                    'linkaja' => 'LinkAja'
                ];
                return $methods[$entry->channel ?? 'dana'] ?? ($entry->channel ?? 'DANA');
            }
        ]);
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'closure',
            'function' => function($entry) {
                $statuses = [
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'success' => 'Success',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled'
                ];
                $status = $statuses[$entry->status] ?? $entry->status;

                $color = '';
                switch($entry->status) {
                    case 'success':
                        $color = 'style="color: green; font-weight: bold;"';
                        break;
                    case 'failed':
                    case 'cancelled':
                        $color = 'style="color: red; font-weight: bold;"';
                        break;
                    case 'pending':
                        $color = 'style="color: orange; font-weight: bold;"';
                        break;
                    case 'processing':
                        $color = 'style="color: blue; font-weight: bold;"';
                        break;
                }
                
                return '<span ' . $color . '>' . $status . '</span>';
            },
            'escaped' => false
        ]);
        CRUD::addColumn([
            'name' => 'total_amount',
            'label' => 'Nilai',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp' . number_format($entry->total_amount, 0, ',', '.');
            },
            'orderable' => true
        ]);
        CRUD::addColumn([
            'name' => 'user.email',
            'label' => 'E-mail Pelanggan',
            'type' => 'closure',
            'function' => function($entry) {
                if (!$entry->user || !$entry->user->email) {
                    return '-';
                }
                
                $email = $entry->user->email;
                if (strlen($email) > 20) {
                    $parts = explode('@', $email);
                    if (count($parts) == 2) {
                        $localPart = $parts[0];
                        $domain = $parts[1];
                        
                        if (strlen($localPart) > 10) {
                            $localPart = substr($localPart, 0, 8) . '...';
                        }
                        
                        return $localPart . '@' . $domain;
                    }
                }
                
                return $email;
            },
            'orderable' => false
        ]);

        // DO NOT ADD FILTERS - Filters are PRO feature
        // Free version only supports search functionality
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
        
        // Add more detailed fields for show
        CRUD::addColumn([
            'name' => 'reference_id',
            'label' => 'Reference ID',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'external_id',
            'label' => 'External ID',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'provider_trx_id',
            'label' => 'Provider Transaction ID',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'customer_ref',
            'label' => 'Customer Reference',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'admin_fee',
            'label' => 'Admin Fee',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp ' . number_format($entry->admin_fee ?? 0, 0, ',', '.');
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'amount',
            'label' => 'Amount (Before Fee)',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp ' . number_format($entry->amount ?? 0, 0, ',', '.');
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'provider_response',
            'label' => 'Provider Response',
            'type' => 'json'
        ]);
        
        CRUD::addColumn([
            'name' => 'processed_at',
            'label' => 'Processed At',
            'type' => 'datetime'
        ]);
        
        CRUD::addColumn([
            'name' => 'expired_at',
            'label' => 'Expired At',
            'type' => 'datetime'
        ]);
        
        CRUD::addColumn([
            'name' => 'notes',
            'label' => 'Notes',
            'type' => 'textarea'
        ]);
    }
}
