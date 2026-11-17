<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $variant = $this->route('variant');
        $variantId = $variant?->getKey();

        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'sku' => ['required', 'string', 'max:96', 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'rental_price_per_day' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
        ];
    }
}
