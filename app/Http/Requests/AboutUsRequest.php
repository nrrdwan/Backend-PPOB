<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AboutUsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('id') ?? $this->id;

        return [
            'type' => [
                'required',
                'string',
                Rule::in(['group_modipay', 'whatsapp_admin', 'instagram']),
                Rule::unique('about_us', 'type')->ignore($id),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'link' => 'nullable|string|max:500',
            'icon_path' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'type' => 'tipe',
            'title' => 'judul',
            'description' => 'deskripsi',
            'link' => 'link/URL',
            'icon_path' => 'icon',
            'is_active' => 'status aktif',
            'order' => 'urutan',
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
            'type.required' => 'Tipe wajib dipilih',
            'type.in' => 'Tipe tidak valid',
            'type.unique' => 'Entry dengan tipe ini sudah ada. Silakan edit entry yang sudah ada.',
            
            'title.required' => 'Judul wajib diisi',
            'title.max' => 'Judul maksimal 255 karakter',
            
            'description.max' => 'Deskripsi maksimal 500 karakter',
            
            'link.max' => 'Link maksimal 500 karakter',
            
            'icon_path.image' => 'File harus berupa gambar',
            'icon_path.mimes' => 'Format icon harus png, jpg, atau jpeg',
            'icon_path.max' => 'Ukuran icon maksimal 1MB',
            
            'is_active.boolean' => 'Status aktif harus berupa true atau false',
            
            'order.integer' => 'Urutan harus berupa angka',
            'order.min' => 'Urutan minimal 0',
        ];
    }
}