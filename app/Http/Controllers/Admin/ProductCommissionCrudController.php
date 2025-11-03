<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductCommissionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ProductCommissionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductCommissionCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\ProductCommission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product-commission');
        CRUD::setEntityNameStrings('komisi produk', 'komisi produk');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addClause('with', 'product');

        CRUD::addColumn([
            'name' => 'product_name',
            'label' => 'Produk',
            'type' => 'custom_html',
            'value' => function($entry) {
                $product = $entry->product;
                if ($product) {
                    return '<strong>' . e($product->name) . '</strong><br>' . 
                           '<small class="text-muted">' . e($product->code) . '</small>';
                }
                return '<span class="text-danger">Product not found</span>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('product', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%')
                      ->orWhere('code', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::addColumn([
            'name' => 'seller_commission_display',
            'label' => 'Komisi Seller',
            'type' => 'custom_html',
            'value' => function($entry) {
                $type = $entry->seller_commission_type === 'percent' ? '%' : 'Rp ';
                $value = $entry->seller_commission_type === 'percent' 
                    ? $entry->seller_commission 
                    : number_format($entry->seller_commission, 0, ',', '.');
                return '<span class="badge bg-info">' . $value . $type . '</span>';
            }
        ]);

        CRUD::addColumn([
            'name' => 'reseller_commission_display',
            'label' => 'Komisi Reseller',
            'type' => 'custom_html',
            'value' => function($entry) {
                $type = $entry->reseller_commission_type === 'percent' ? '%' : 'Rp ';
                $value = $entry->reseller_commission_type === 'percent' 
                    ? $entry->reseller_commission 
                    : number_format($entry->reseller_commission, 0, ',', '.');
                return '<span class="badge bg-success">' . $value . $type . '</span>';
            }
        ]);

        CRUD::addColumn([
            'name' => 'b2b_commission_display',
            'label' => 'Komisi B2B',
            'type' => 'custom_html',
            'value' => function($entry) {
                $type = $entry->b2b_commission_type === 'percent' ? '%' : 'Rp ';
                $value = $entry->b2b_commission_type === 'percent' 
                    ? $entry->b2b_commission 
                    : number_format($entry->b2b_commission, 0, ',', '.');
                return '<span class="badge bg-warning">' . $value . $type . '</span>';
            }
        ]);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Status',
            'type' => 'boolean',
            'options' => [0 => 'Nonaktif', 1 => 'Aktif'],
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => 'Terakhir Diubah',
            'type' => 'datetime',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProductCommissionRequest::class);

        CRUD::addField([
            'name' => 'product_id',
            'label' => 'Produk',
            'type' => 'select',
            'entity' => 'product',
            'model' => 'App\Models\Product',
            'attribute' => 'name',
            'options' => (function ($query) {
                return $query->select('id', 'name', 'code')
                           ->where('is_active', true)
                           ->get()
                           ->mapWithKeys(function ($item) {
                               return [$item->id => $item->name . ' (' . $item->code . ')'];
                           });
            }),
        ]);

        CRUD::addField([
            'name' => 'seller_section',
            'type' => 'custom_html',
            'value' => '<h5 class="mt-4 mb-3 text-primary">Komisi Seller</h5><hr>',
        ]);

        CRUD::addField([
            'name' => 'seller_commission',
            'label' => 'Nilai Komisi Seller',
            'type' => 'number',
            'attributes' => ['step' => 'any', 'min' => 0],
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'seller_commission_type',
            'label' => 'Tipe Komisi Seller',
            'type' => 'select_from_array',
            'options' => [
                'percent' => 'Persentase (%)',
                'fixed' => 'Nominal Tetap (Rp)',
            ],
            'default' => 'percent',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'reseller_section',
            'type' => 'custom_html',
            'value' => '<h5 class="mt-4 mb-3 text-success">Komisi Reseller</h5><hr>',
        ]);

        CRUD::addField([
            'name' => 'reseller_commission',
            'label' => 'Nilai Komisi Reseller',
            'type' => 'number',
            'attributes' => ['step' => 'any', 'min' => 0],
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'reseller_commission_type',
            'label' => 'Tipe Komisi Reseller',
            'type' => 'select_from_array',
            'options' => [
                'percent' => 'Persentase (%)',
                'fixed' => 'Nominal Tetap (Rp)',
            ],
            'default' => 'percent',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'b2b_section',
            'type' => 'custom_html',
            'value' => '<h5 class="mt-4 mb-3 text-warning">Komisi B2B</h5><hr>',
        ]);

        CRUD::addField([
            'name' => 'b2b_commission',
            'label' => 'Nilai Komisi B2B',
            'type' => 'number',
            'attributes' => ['step' => 'any', 'min' => 0],
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'b2b_commission_type',
            'label' => 'Tipe Komisi B2B',
            'type' => 'select_from_array',
            'options' => [
                'percent' => 'Persentase (%)',
                'fixed' => 'Nominal Tetap (Rp)',
            ],
            'default' => 'percent',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'additional_section',
            'type' => 'custom_html',
            'value' => '<h5 class="mt-4 mb-3 text-info">Pengaturan Tambahan</h5><hr>',
        ]);

        CRUD::addField([
            'name' => 'min_commission',
            'label' => 'Komisi Minimum (Opsional)',
            'type' => 'number',
            'attributes' => ['step' => 'any', 'min' => 0],
            'hint' => 'Batas minimal komisi yang diberikan',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'max_commission',
            'label' => 'Komisi Maksimum (Opsional)',
            'type' => 'number',
            'attributes' => ['step' => 'any', 'min' => 0],
            'hint' => 'Batas maksimal komisi yang diberikan',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Status Aktif',
            'type' => 'boolean',
            'default' => true,
        ]);
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
