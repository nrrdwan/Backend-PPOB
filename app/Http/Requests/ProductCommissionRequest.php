<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCommissionRequest extends FormRequest
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
        return [
            'product_id' => 'required|exists:products,id',
            'seller_commission' => 'required|numeric|min:0',
            'seller_commission_type' => 'required|in:percent,fixed',
            'reseller_commission' => 'required|numeric|min:0',
            'reseller_commission_type' => 'required|in:percent,fixed',
            'b2b_commission' => 'required|numeric|min:0',
            'b2b_commission_type' => 'required|in:percent,fixed',
            'min_commission' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0|gte:min_commission',
            'is_active' => 'boolean',
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
            'product_id' => 'Produk',
            'seller_commission' => 'Komisi Seller',
            'seller_commission_type' => 'Tipe Komisi Seller',
            'reseller_commission' => 'Komisi Reseller',
            'reseller_commission_type' => 'Tipe Komisi Reseller',
            'b2b_commission' => 'Komisi B2B',
            'b2b_commission_type' => 'Tipe Komisi B2B',
            'min_commission' => 'Komisi Minimum',
            'max_commission' => 'Komisi Maksimum',
            'is_active' => 'Status Aktif',
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
            'product_id.required' => 'Produk harus dipilih.',
            'product_id.exists' => 'Produk yang dipilih tidak valid.',
            'seller_commission.required' => 'Komisi seller harus diisi.',
            'seller_commission.numeric' => 'Komisi seller harus berupa angka.',
            'seller_commission.min' => 'Komisi seller tidak boleh negatif.',
            'reseller_commission.required' => 'Komisi reseller harus diisi.',
            'reseller_commission.numeric' => 'Komisi reseller harus berupa angka.',
            'reseller_commission.min' => 'Komisi reseller tidak boleh negatif.',
            'b2b_commission.required' => 'Komisi B2B harus diisi.',
            'b2b_commission.numeric' => 'Komisi B2B harus berupa angka.',
            'b2b_commission.min' => 'Komisi B2B tidak boleh negatif.',
            'max_commission.gte' => 'Komisi maksimum harus lebih besar atau sama dengan komisi minimum.',
        ];
    }
}
