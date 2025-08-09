<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RoleRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Support\Facades\Log;

/**
 * Class RoleCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RoleCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Role::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/role');
        CRUD::setEntityNameStrings('role', 'roles');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Nama Role');
        CRUD::column('slug')->label('Slug');
        CRUD::column('description')->label('Deskripsi');
        CRUD::column('permissions')->label('Permissions')->type('relationship_count');
        CRUD::column('users')->label('Total Users')->type('relationship_count');
        CRUD::column('is_active')->label('Status')->type('boolean');
        CRUD::column('created_at')->label('Dibuat')->type('datetime');

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RoleRequest::class);

        CRUD::field('name')
            ->label('Nama Role')
            ->type('text')
            ->validationRules('required|min:2');

        CRUD::field('description')
            ->label('Deskripsi')
            ->type('textarea')
            ->validationRules('nullable');

        CRUD::field('is_active')
            ->label('Status Aktif')
            ->type('boolean')
            ->default(1);

        // Dapatkan permissions yang aktif
        $permissions = \App\Models\Permission::where('is_active', true)->get();
        $permissionOptions = [];
        foreach ($permissions as $permission) {
            $permissionOptions[$permission->id] = $permission->name . ' (' . $permission->description . ')';
        }

        // Field permissions dengan custom view sederhana
        CRUD::field([
            'name' => 'permissions',
            'label' => 'Permissions',
            'type' => 'view',
            'view' => 'custom.fields.permissions_checkboxes',
            'permissions_data' => $permissionOptions,
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

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // Execute the FormRequest authorization and validation
        $request = $this->crud->validateRequest();

        // Handle permissions relationship
        $permissions = $request->input('permissions', []);
        
        // Ensure permissions is an array
        if (!is_array($permissions)) {
            $permissions = [];
        }
        
        // Remove permissions from request data to avoid mass assignment issues
        $requestData = $request->except('permissions');

        // Create the role first
        $item = $this->crud->create($requestData);

        // Then attach the permissions
        if (!empty($permissions)) {
            $item->permissions()->sync($permissions);
        }

        $this->data['entry'] = $this->crud->entry = $item;

        // Show success message
        Alert::success(trans('backpack::crud.insert_success'))->flash();

        // Save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // Execute the FormRequest authorization and validation
        $request = $this->crud->validateRequest();

        // Handle permissions relationship
        $permissions = $request->input('permissions', []);
        
        // Remove permissions from request data
        $requestData = $request->except('permissions');

        // Get the ID from route
        $id = $this->crud->getCurrentEntryId();
        
        // Update the role
        $item = $this->crud->update($id, $requestData);

        // Sync the permissions
        if (is_array($permissions)) {
            $item->permissions()->sync($permissions);
        }

        $this->data['entry'] = $this->crud->entry = $item;

        // Show success message
        Alert::success(trans('backpack::crud.update_success'))->flash();

        // Save the redirect choice for next time
        $this->crud->setSaveAction();

        return $this->crud->performSaveAction($item->getKey());
    }
}
