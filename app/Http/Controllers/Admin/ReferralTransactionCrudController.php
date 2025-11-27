<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ReferralRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ReferralTransactionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ReferralTransactionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\ReferralTransaction::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/referral-transaction');
        CRUD::setEntityNameStrings('referral transaction', 'referral transactions');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // Referrer Info
        CRUD::addColumn([
            'name' => 'referrer_info',
            'label' => 'Referrer',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->referrer->name . '<br><small class="text-muted">' . $entry->referrer->email . '</small>';
            },
            'escaped' => false,
        ]);

        // Referred User Info
        CRUD::addColumn([
            'name' => 'referred_info',
            'label' => 'Referred User',
            'type' => 'closure',
            'function' => function($entry) {
                return $entry->referred->name . '<br><small class="text-muted">' . $entry->referred->email . '</small>';
            },
            'escaped' => false,
        ]);

        // Referral Code
        CRUD::addColumn([
            'name' => 'referral_code',
            'label' => 'Referral Code',
            'type' => 'text',
        ]);

        // Referral Number
        CRUD::addColumn([
            'name' => 'referral_number',
            'label' => 'Referral #',
            'type' => 'number',
            'suffix' => '',
        ]);

        // Commission Amount
        CRUD::addColumn([
            'name' => 'commission_amount',
            'label' => 'Commission',
            'type' => 'closure',
            'function' => function($entry) {
                return '<strong class="text-success">Rp ' . number_format($entry->commission_amount, 0, ',', '.') . '</strong>';
            },
            'escaped' => false,
        ]);

        // Status
        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'badge',
            'options' => [
                'paid' => 'success',
                'pending' => 'warning',
                'cancelled' => 'danger',
            ],
        ]);

        // Created At
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Date',
            'type' => 'datetime',
        ]);

        // Filters
        CRUD::addFilter([
            'name' => 'status',
            'type' => 'select2',
            'label' => 'Status'
        ], [
            'paid' => 'Paid',
            'pending' => 'Pending',
            'cancelled' => 'Cancelled',
        ], function($value) {
            CRUD::addClause('where', 'status', $value);
        });

        CRUD::addFilter([
            'type' => 'date_range',
            'name' => 'created_at',
            'label' => 'Date Range'
        ],
        false,
        function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'created_at', '>=', $dates->from);
            CRUD::addClause('where', 'created_at', '<=', $dates->to . ' 23:59:59');
        });

        // Buttons
        CRUD::removeButton('create');
        CRUD::removeButton('update');
        CRUD::removeButton('delete');
        CRUD::addButton('line', 'view_referrer', 'view', 'beginning');
    }

    /**
     * Define what happens when the Show operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-show
     * @return void
     */
    protected function setupShowOperation()
    {
        CRUD::addColumn([
            'name' => 'id',
            'label' => 'ID',
        ]);

        CRUD::addColumn([
            'name' => 'referrer',
            'label' => 'Referrer',
            'type' => 'relationship',
            'attribute' => 'name',
            'suffix' => function($entry) {
                return '<br><small class="text-muted">' . $entry->referrer->email . '</small>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'referred',
            'label' => 'Referred User',
            'type' => 'relationship',
            'attribute' => 'name',
            'suffix' => function($entry) {
                return '<br><small class="text-muted">' . $entry->referred->email . '</small>';
            },
            'escaped' => false,
        ]);

        CRUD::addColumn([
            'name' => 'referral_code',
            'label' => 'Referral Code',
        ]);

        CRUD::addColumn([
            'name' => 'referral_number',
            'label' => 'Referral Number',
        ]);

        CRUD::addColumn([
            'name' => 'commission_amount',
            'label' => 'Commission Amount',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp ' . number_format($entry->commission_amount, 0, ',', '.');
            },
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Status',
            'type' => 'badge',
            'options' => [
                'paid' => 'success',
                'pending' => 'warning',
                'cancelled' => 'danger',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Created At',
            'type' => 'datetime',
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Updated At',
            'type' => 'datetime',
        ]);
    }
}