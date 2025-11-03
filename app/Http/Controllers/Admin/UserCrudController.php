<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Nama');
        CRUD::column('email')->label('Email');
        CRUD::addColumn([
            'name' => 'balance',
            'label' => 'Saldo',
            'type' => 'closure',
            'function' => function($entry) {
                return 'Rp ' . number_format($entry->balance, 0, ',', '.');
            },
            'orderable' => true,
            'searchLogic' => false
        ]);

        $roles = \App\Models\Role::where('is_active', true)->pluck('name', 'name')->toArray();
        
        CRUD::column('role')->label('Role')->type('badge')
            ->options($roles)
            ->colors([
                'Administrator' => 'success',
                'Agen PPOB' => 'warning', 
                'User Biasa' => 'info'
            ]);
        CRUD::column('is_active')->label('Status')->type('boolean')
            ->options([
                0 => 'Tidak Aktif',
                1 => 'Aktif'
            ]);
        CRUD::column('last_login_at')->label('Login Terakhir')->type('datetime');
        CRUD::column('created_at')->label('Dibuat')->type('datetime');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserRequest::class);

        $roles = \App\Models\Role::where('is_active', true)->pluck('name', 'name')->toArray();
        
        $defaultRole = array_key_exists('User Biasa', $roles) ? 'User Biasa' : array_key_first($roles);
        
        CRUD::field('name')->label('Nama')->type('text')->validationRules('required|min:2');
        CRUD::field('email')->label('Email')->type('email')->validationRules('required|email|unique:users,email');
        CRUD::field('password')->label('Password')->type('password')->validationRules('required|min:8');
        CRUD::addField([
            'name' => 'balance',
            'label' => 'Saldo Awal',
            'type' => 'number',
            'attributes' => [
                'step' => '1000',
                'min' => '0',
                'max' => '100000000'
            ],
            'default' => 0,
            'prefix' => 'Rp',
            'suffix' => '.00',
            'hint' => 'Saldo awal user dalam rupiah'
        ]);
        
        CRUD::field('role')->label('Role')->type('select_from_array')
            ->options($roles)
            ->default($defaultRole)
            ->validationRules('required|in:' . implode(',', array_keys($roles)));
        CRUD::field('is_active')->label('Status Aktif')->type('boolean')->default(1);
    }

    /**
     * Handle the store request before saving to database.
     * Password akan di-hash otomatis oleh model cast.
     */
    public function store()
    {
        return $this->traitStore();
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $roles = \App\Models\Role::where('is_active', true)->pluck('name', 'name')->toArray();
        
        CRUD::field('name')->label('Nama')->type('text')->validationRules('required|min:2');
        CRUD::field('email')->label('Email')->type('email')->validationRules('required|email|unique:users,email,' . CRUD::getCurrentEntryId());
        CRUD::field('password')->label('Password (kosongkan jika tidak ingin mengubah)')->type('password')->validationRules('nullable|min:8');
        CRUD::addField([
            'name' => 'balance',
            'label' => 'Saldo',
            'type' => 'number',
            'attributes' => [
                'step' => '1000',
                'min' => '0',
                'max' => '100000000'
            ],
            'prefix' => 'Rp',
            'suffix' => '.00',
            'hint' => 'Saldo user dalam rupiah (admin dapat menyesuaikan)'
        ]);
        
        CRUD::field('role')->label('Role')->type('select_from_array')
            ->options($roles)
            ->validationRules('required|in:' . implode(',', array_keys($roles)));
        CRUD::field('is_active')->label('Status Aktif')->type('boolean');
    }

    /**
     * Handle the update request before saving to database.
     * Remove password field if it's empty to prevent overwriting existing password.
     */
    public function update()
    {
        $request = CRUD::getRequest();
        
        if (empty($request->input('password'))) {
            $request->request->remove('password');
        }
        return $this->traitUpdate();
    }
}
