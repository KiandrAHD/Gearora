<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug')
                ? Str::slug((string) $this->slug)
                : Str::slug((string) $this->name),
            'is_featured' => $this->boolean('is_featured'),
            'requires_deposit' => $this->boolean('requires_deposit'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->getKey();

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:3', 'max:180'],
            'slug' => ['required', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('products', 'slug')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'brand' => ['nullable', 'string', 'max:120'],
            'type' => ['required', Rule::in(ProductType::values())],
            'status' => ['required', Rule::in(ProductStatus::values())],
            'rental_price_per_day' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'required_if:type,rental,both'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'required_if:type,sale,both'],
            'requires_deposit' => ['boolean'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99', 'required_if:requires_deposit,true'],
            'is_featured' => ['boolean'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.key' => ['nullable', 'string', 'max:80'],
            'specifications.*.value' => ['nullable', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rental_price_per_day.required_if' => 'Produk sewa wajib memiliki harga sewa per hari.',
            'sale_price.required_if' => 'Produk jual wajib memiliki harga jual per unit.',
            'deposit_amount.required_if' => 'Nominal deposit wajib diisi bila produk memerlukan deposit.',
        ];
    }

    /**
     * Spesifikasi diubah menjadi map key => value, membuang baris kosong.
     *
     * @return array<string, string>
     */
    public function specifications(): array
    {
        $rows = $this->validated('specifications') ?? [];
        $result = [];

        foreach ($rows as $row) {
            $key = trim((string) ($row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));

            if ($key !== '' && $value !== '') {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
