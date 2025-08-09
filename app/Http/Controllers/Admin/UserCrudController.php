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
        
        // Get roles from database dynamically
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
        
        // Get roles from database dynamically
        $roles = \App\Models\Role::where('is_active', true)->pluck('name', 'name')->toArray();
        
        // Set default role - use first available role if 'User Biasa' not found
        $defaultRole = array_key_exists('User Biasa', $roles) ? 'User Biasa' : array_key_first($roles);
        
        CRUD::field('name')->label('Nama')->type('text')->validationRules('required|min:2');
        CRUD::field('email')->label('Email')->type('email')->validationRules('required|email|unique:users,email');
        CRUD::field('password')->label('Password')->type('password')->validationRules('required|min:8');
        CRUD::field('role')->label('Role')->type('select_from_array')
            ->options($roles)
            ->default($defaultRole)
            ->validationRules('required|in:' . implode(',', array_keys($roles)));
        CRUD::field('is_active')->label('Status Aktif')->type('boolean')->default(1);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        // Get roles from database dynamically
        $roles = \App\Models\Role::where('is_active', true)->pluck('name', 'name')->toArray();
        
        CRUD::field('name')->label('Nama')->type('text')->validationRules('required|min:2');
        CRUD::field('email')->label('Email')->type('email')->validationRules('required|email|unique:users,email,' . CRUD::getCurrentEntryId());
        CRUD::field('password')->label('Password (kosongkan jika tidak ingin mengubah)')->type('password')->validationRules('nullable|min:8');
        CRUD::field('role')->label('Role')->type('select_from_array')
            ->options($roles)
            ->validationRules('required|in:' . implode(',', array_keys($roles)));
        CRUD::field('is_active')->label('Status Aktif')->type('boolean');
    }
}
