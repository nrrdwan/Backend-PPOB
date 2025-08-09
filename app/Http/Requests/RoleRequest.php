<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('role') ? $this->route('role') : ($this->route('id') ?? null);
        
        $rules = [
            'name' => 'required|min:2|max:255|unique:roles,name' . ($id ? ',' . $id : ''),
            'description' => 'nullable|max:500',
            'is_active' => 'boolean',
            'permissions' => 'nullable', // Remove array requirement for now
        ];

        return $rules;
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'nama role',
            'description' => 'deskripsi',
            'permissions' => 'permissions',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Nama role sudah digunakan.',
            'permissions.array' => 'Permissions harus berupa array.',
            'permissions.*.exists' => 'Permission yang dipilih tidak valid.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Handle permissions field yang bisa datang dalam berbagai format
        $permissions = $this->input('permissions');
        
        if ($permissions === null || $permissions === '') {
            // Jika null atau empty string, set sebagai array kosong
            $this->merge(['permissions' => []]);
        } elseif (is_string($permissions)) {
            // Jika string (single value), convert ke array
            $this->merge(['permissions' => [$permissions]]);
        } elseif (is_object($permissions)) {
            // Jika object, convert ke array of IDs
            $permissionIds = [];
            foreach ($permissions as $permission) {
                if (is_object($permission) && isset($permission->id)) {
                    $permissionIds[] = $permission->id;
                } elseif (is_array($permission) && isset($permission['id'])) {
                    $permissionIds[] = $permission['id'];
                }
            }
            $this->merge(['permissions' => $permissionIds]);
        } elseif (is_array($permissions)) {
            // Jika sudah array, pastikan berisi ID saja
            $permissionIds = [];
            foreach ($permissions as $permission) {
                if (is_numeric($permission)) {
                    $permissionIds[] = (int)$permission;
                } elseif (is_object($permission) && isset($permission->id)) {
                    $permissionIds[] = $permission->id;
                } elseif (is_array($permission) && isset($permission['id'])) {
                    $permissionIds[] = $permission['id'];
                }
            }
            $this->merge(['permissions' => $permissionIds]);
        }
    }
}
