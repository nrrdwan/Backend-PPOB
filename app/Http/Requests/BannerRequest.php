<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
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
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'promo_code' => 'nullable|string|max:50',
            'valid_until' => 'nullable|date',
            'terms_conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Untuk create operation, image_url wajib
        if ($this->isMethod('post')) {
            $rules['image_url'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
        } 
        // Untuk update operation, image_url opsional
        else if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['image_url'] = 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048';
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
            'title' => 'judul banner',
            'image_url' => 'gambar banner',
            'description' => 'deskripsi promo',
            'promo_code' => 'kode promo',
            'valid_until' => 'masa berlaku',
            'terms_conditions' => 'syarat & ketentuan',
            'is_active' => 'status aktif',
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
            'title.required' => 'Judul banner wajib diisi',
            'title.string' => 'Judul banner harus berupa teks',
            'title.max' => 'Judul banner maksimal 255 karakter',
            
            'image_url.required' => 'Gambar banner wajib diupload',
            'image_url.image' => 'File harus berupa gambar',
            'image_url.mimes' => 'Format gambar harus jpeg, png, jpg, atau gif',
            'image_url.max' => 'Ukuran gambar maksimal 2MB',
            
            'description.string' => 'Deskripsi harus berupa teks',
            
            'promo_code.string' => 'Kode promo harus berupa teks',
            'promo_code.max' => 'Kode promo maksimal 50 karakter',
            
            'valid_until.date' => 'Format tanggal tidak valid',
            
            'terms_conditions.string' => 'Syarat & ketentuan harus berupa teks',
            
            'is_active.boolean' => 'Status aktif harus berupa true atau false',
        ];
    }
}