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
        
        // Set default order by created_at desc
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
        // Basic columns only
        CRUD::addColumn([
            'name' => 'transaction_id',
            'label' => 'ID Transaksi',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'user.name',
            'label' => 'User',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'type',
            'label' => 'Tipe',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'total_amount',
            'label' => 'Total',
            'type' => 'number',
            'suffix' => ' IDR'
        ]);
        
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Tanggal',
            'type' => 'datetime'
        ]);
        
        // Simple filters only
        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'status',
            'label' => 'Status'
        ], [
            'pending' => 'Pending',
            'processing' => 'Processing', 
            'success' => 'Success',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ], function($value) {
            CRUD::addClause('where', 'status', $value);
        });
        
        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'type',
            'label' => 'Tipe'
        ], [
            'topup' => 'Top Up',
            'pulsa' => 'Pulsa',
            'pln' => 'PLN',
            'pdam' => 'PDAM',
            'game' => 'Game',
            'emoney' => 'E-Money',
            'other' => 'Lainnya'
        ], function($value) {
            CRUD::addClause('where', 'type', $value);
        });
    }
}
        
        CRUD::addColumn([
            'name' => 'user',
            'label' => 'User',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->user ? $entry->user->name . ' (' . $entry->user->email . ')' : 'N/A';
            },
            'orderable' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                      ->orWhere('email', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'product',
            'label' => 'Produk',
            'type' => 'closure',
            'function' => function($entry) {
                if ($entry->type === 'topup') {
                    return 'Top Up Saldo';
                }
                return $entry->product ? $entry->product->name : 'N/A';
            },
            'orderable' => false,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('product', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'type',
            'label' => 'Tipe',
            'type' => 'badge',
            'options' => [
                'topup' => 'Top Up',
                'pulsa' => 'Pulsa',
                'pln' => 'PLN',
                'pdam' => 'PDAM',
                'game' => 'Game',
                'emoney' => 'E-Money',
                'other' => 'Lainnya'
            ],
            'colors' => [
                'topup' => 'success',
                'pulsa' => 'primary',
                'pln' => 'warning',
                'pdam' => 'info',
                'game' => 'secondary',
                'emoney' => 'dark',
                'other' => 'light'
            ]
        ]);
        
        CRUD::addColumn([
            'name' => 'total_amount',
            'label' => 'Total',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp ' . number_format($entry->total_amount, 0, ',', '.');
            },
            'orderable' => true
        ]);
        
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'badge',
            'options' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'success' => 'Success',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled'
            ],
            'colors' => [
                'pending' => 'warning',
                'processing' => 'info',
                'success' => 'success',
                'failed' => 'danger',
                'cancelled' => 'secondary'
            ]
        ]);
        
        CRUD::addColumn([
            'name' => 'phone_number',
            'label' => 'No. HP',
            'type' => 'text'
        ]);
        
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Tanggal',
            'type' => 'datetime'
        ]);
        
        CRUD::addColumn([
            'name' => 'processed_at',
            'label' => 'Diproses',
            'type' => 'datetime'
        ]);
        
        // Add filters
        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'status',
            'label' => 'Status'
        ], [
            'pending' => 'Pending',
            'processing' => 'Processing', 
            'success' => 'Success',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled'
        ], function($value) {
            CRUD::addClause('where', 'status', $value);
        });
        
        CRUD::addFilter([
            'type' => 'dropdown',
            'name' => 'type',
            'label' => 'Tipe'
        ], [
            'topup' => 'Top Up',
            'pulsa' => 'Pulsa',
            'pln' => 'PLN',
            'pdam' => 'PDAM',
            'game' => 'Game',
            'emoney' => 'E-Money',
            'other' => 'Lainnya'
        ], function($value) {
            CRUD::addClause('where', 'type', $value);
        });
        
        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'created_at',
            'label' => 'Tanggal'
        ], false, function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'created_at', '>=', $dates->from);
            CRUD::addClause('where', 'created_at', '<=', $dates->to . ' 23:59:59');
        });
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TransactionRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
