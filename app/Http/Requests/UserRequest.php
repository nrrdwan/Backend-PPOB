<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        $id = $this->route('user') ? $this->route('user') : ($this->route('id') ?? null);
        
        // Get available roles dynamically
        $availableRoles = \App\Models\Role::where('is_active', true)->pluck('name')->toArray();
        $roleValidation = 'required|in:' . implode(',', $availableRoles);
        
        $rules = [
            'name' => 'required|min:2|max:255',
            'email' => 'required|email|unique:users,email' . ($id ? ',' . $id : ''),
            'role' => $roleValidation,
            'is_active' => 'boolean',
        ];
        
        // Password rules - required for create, optional for update
        if ($this->isMethod('post')) {
            $rules['password'] = 'required|min:8';
        } else {
            $rules['password'] = 'nullable|min:8';
        }
        
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
            //
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
            //
        ];
    }
}
