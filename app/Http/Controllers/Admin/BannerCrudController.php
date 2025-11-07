<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\BannerRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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

    protected function setupListOperation()
    {
        CRUD::column('title')->label('Judul Banner');

        CRUD::addColumn([
            'name' => 'image_url',
            'label' => 'Preview Banner',
            'type' => 'image',
            'height' => '80px',
            'width'  => '160px',
            'prefix' => 'storage/', // ✅ PASTIKAN PREEFIX BENAR
        ]);

        CRUD::column('description')
            ->label('Deskripsi')
            ->limit(50)
            ->wrapper([
                'element' => 'span',
                'title' => function ($crud, $column, $entry, $related_key) {
                    return $entry->description;
                },
            ]);

        CRUD::column('promo_code')
            ->label('Kode Promo')
            ->type('text');

        CRUD::addColumn([
            'name' => 'valid_until',
            'label' => 'Berlaku Sampai',
            'type' => 'datetime',
            'format' => 'DD MMMM YYYY HH:mm',
        ]);

        CRUD::column('is_active')
            ->type('boolean')
            ->label('Aktif')
            ->options([
                0 => 'Tidak Aktif',
                1 => 'Aktif'
            ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(BannerRequest::class);

        CRUD::addField([
            'name' => 'title',
            'label' => 'Judul Banner',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'Contoh: Promo Akhir Tahun'
            ],
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'image_url',
            'label' => 'Upload Gambar Banner',
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public', // ✅ DISK PUBLIC UNTUK LOCAL STORAGE
            'attributes' => [
                'accept' => 'image/*',
            ],
            'hint' => 'Format: JPEG, PNG, JPG, GIF | Maksimal: 2MB',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => 'Deskripsi Promo',
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => 'Deskripsi lengkap tentang promo...',
                'rows' => 4,
            ],
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'promo_code',
            'label' => 'Kode Promo (Opsional)',
            'type' => 'text',
            'attributes' => [
                'placeholder' => 'Contoh: PROMO50, DISKON30, dll.',
            ],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'valid_until',
            'label' => 'Masa Berlaku (Opsional)',
            'type' => 'datetime',
            'attributes' => [
                'placeholder' => 'Pilih tanggal kadaluarsa promo',
            ],
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'terms_conditions',
            'label' => 'Syarat & Ketentuan',
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => 'Masukkan syarat dan ketentuan promo (pisahkan dengan enter)...',
                'rows' => 6,
            ],
            'hint' => 'Gunakan enter untuk memisahkan setiap poin syarat & ketentuan',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name' => 'is_active',
            'label' => 'Aktifkan Banner',
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
        CRUD::column('title')->label('Judul Banner');

        CRUD::addColumn([
            'name' => 'image_url',
            'label' => 'Gambar Banner',
            'type' => 'image',
            'height' => '200px',
            'prefix' => 'storage/', // ✅ PASTIKAN PREEFIX BENAR
        ]);

        CRUD::column('description')
            ->label('Deskripsi Promo')
            ->type('textarea');

        CRUD::column('promo_code')
            ->label('Kode Promo');

        CRUD::addColumn([
            'name' => 'valid_until',
            'label' => 'Berlaku Sampai',
            'type' => 'datetime',
            'format' => 'DD MMMM YYYY HH:mm',
        ]);

        CRUD::addColumn([
            'name' => 'terms_conditions',
            'label' => 'Syarat & Ketentuan',
            'type' => 'textarea',
            'escaped' => false,
            'limit' => 1000,
        ]);

        CRUD::column('is_active')
            ->type('boolean')
            ->label('Status Aktif')
            ->options([
                0 => '<span style="color: red">Tidak Aktif</span>',
                1 => '<span style="color: green">Aktif</span>'
            ]);
    }
}