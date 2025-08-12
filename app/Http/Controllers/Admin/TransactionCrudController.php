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
        // Define columns manually without using setFromDb to have more control
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
            'prefix' => 'Rp ',
            'decimals' => 0
        ]);
        
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'text'
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
            'name' => 'admin_fee',
            'label' => 'Admin Fee',
            'type' => 'number',
            'prefix' => 'Rp ',
            'decimals' => 0
        ]);
        
        CRUD::addColumn([
            'name' => 'callback_data',
            'label' => 'Callback Data',
            'type' => 'textarea'
        ]);
    }
}
