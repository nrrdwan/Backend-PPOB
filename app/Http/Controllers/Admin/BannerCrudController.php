<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BannerRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class BannerCrudController
 * @package App\Http\Controllers\Admin
 */
class BannerCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Banner::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/banner');
        CRUD::setEntityNameStrings('Banner', 'Banners');
    }

    /**
     * Tampilkan daftar banner (dengan preview gambar)
     */
    protected function setupListOperation()
    {
        CRUD::column('title')->label('Judul Banner');

        CRUD::addColumn([
            'name' => 'image_url',
            'label' => 'Preview Banner',
            'type' => 'image',
            'prefix' => 'storage/',
            'height' => '80px',
            'width'  => '160px',
        ]);

        CRUD::column('is_active')
            ->type('boolean')
            ->label('Aktif');
    }

    /**
     * Form create banner baru (upload file ke storage)
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BannerRequest::class);

        CRUD::field('title')
            ->label('Judul Banner')
            ->type('text')
            ->attributes(['placeholder' => 'Contoh: Promo Akhir Tahun']);

        CRUD::addField([
            'name'   => 'image_url',
            'label'  => 'Upload Gambar Banner',
            'type'   => 'upload',   // ini penting supaya muncul tombol file upload
            'upload' => true,
            'disk'   => 'public',   // simpan ke storage/app/public
        ]);

        CRUD::field('is_active')
            ->type('checkbox')
            ->label('Aktif');
    }

    /**
     * Form edit/update banner
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}