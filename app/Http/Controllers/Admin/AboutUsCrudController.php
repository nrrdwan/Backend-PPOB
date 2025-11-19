<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\AboutUsRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\AboutUs;

class AboutUsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(AboutUs::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/about-us');
        CRUD::setEntityNameStrings('about us', 'about us');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'order',
            'label' => 'Urutan',
            'type' => 'number',
        ]);

        CRUD::addColumn([
            'name' => 'type',
            'label' => 'Tipe',
            'type' => 'select_from_array',
            'options' => [
                'group_modipay' => 'Group Modipay',
                'whatsapp_admin' => 'WhatsApp Admin',
                'instagram' => 'Instagram',
            ],
        ]);

        CRUD::addColumn([
            'name' => 'title',
            'label' => 'Judul',
            'type' => 'text',
        ]);

        CRUD::addColumn([
            'name' => 'link',
            'label' => 'Link',
            'type' => 'text',
            'limit' => 50,
        ]);

        CRUD::addColumn([
            'name' => 'is_active',
            'label' => 'Status',
            'type' => 'boolean',
            'options' => [
                0 => 'Tidak Aktif',
                1 => 'Aktif'
            ],
        ]);

        CRUD::orderBy('order', 'asc');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(AboutUsRequest::class);

        CRUD::addField([
            'name' => 'type',
            'label' => 'Tipe',
            'type' => 'select_from_array',
            'options' => [
                'group_modipay' => 'Group Modipay',
                'whatsapp_admin' => 'WhatsApp Admin',
                'instagram' => 'Instagram',
            ],
            'allows_null' => false,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'order',
            'label' => 'Urutan',
            'type' => 'number',
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Judul',
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Deskripsi',
            'type' => 'textarea',
            'wrapper' => ['class' => 'form-group col-md-12'],
            'attributes' => [
                'rows' => 3,
            ],
        ]);

        CRUD::addField([
            'name' => 'link',
            'label' => 'Link/URL',
            'type' => 'url',
            'wrapper' => ['class' => 'form-group col-md-12'],
            'hint' => 'Contoh: https://wa.me/628123456789 atau https://instagram.com/username',
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Aktifkan',
            'type' => 'checkbox',
            'default' => true,
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}