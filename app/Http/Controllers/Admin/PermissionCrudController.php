<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PermissionRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PermissionCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PermissionCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Permission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/permission');
        CRUD::setEntityNameStrings('permission', 'permissions');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Nama Permission');
        CRUD::column('slug')->label('Slug');
        CRUD::column('group')->label('Group')->type('badge')
            ->colors([
                'user' => 'primary',
                'role' => 'success',
                'product' => 'warning',
                'transaction' => 'info',
                'general' => 'secondary'
            ]);
        CRUD::column('description')->label('Deskripsi');
        CRUD::column('roles')->label('Total Roles')->type('relationship_count');
        CRUD::column('is_active')->label('Status')->type('boolean');
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
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|unique:permissions,slug',
            'description' => 'nullable|max:500',
            'group' => 'required|in:user,role,product,transaction,general',
            'is_active' => 'boolean'
        ]);
        
        CRUD::field('name')->label('Nama Permission')->type('text');
        CRUD::field('slug')->label('Slug')->type('text')->hint('Otomatis diisi jika kosong');
        CRUD::field('group')->label('Group')->type('select_from_array')
            ->options([
                'user' => 'User Management',
                'role' => 'Role & Permission',
                'product' => 'Product Management',
                'transaction' => 'Transaction Management',
                'general' => 'General'
            ]);
        CRUD::field('description')->label('Deskripsi')->type('textarea');
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
        CRUD::setValidation([
            'name' => 'required|min:2|max:255',
            'slug' => 'nullable|unique:permissions,slug,' . CRUD::getCurrentEntryId(),
            'description' => 'nullable|max:500',
            'group' => 'required|in:user,role,product,transaction,general',
            'is_active' => 'boolean'
        ]);
        
        CRUD::field('name')->label('Nama Permission')->type('text');
        CRUD::field('slug')->label('Slug')->type('text')->hint('Otomatis diisi jika kosong');
        CRUD::field('group')->label('Group')->type('select_from_array')
            ->options([
                'user' => 'User Management',
                'role' => 'Role & Permission',
                'product' => 'Product Management',
                'transaction' => 'Transaction Management',
                'general' => 'General'
            ]);
        CRUD::field('description')->label('Deskripsi')->type('textarea');
        CRUD::field('is_active')->label('Status Aktif')->type('boolean');
    }
}
