<?php

namespace App\Http\Requests\PublicSite;

use Illuminate\Validation\Rule;

class TripPackageRequest extends TripPlannerRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $items = array_values(array_filter(
            (array) $this->input('items', []),
            fn ($item) => is_array($item) && (int) ($item['variant_id'] ?? 0) > 0,
        ));

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.(int) config('gearora.sales.max_quantity_per_line', 99)],
            'items.*.fulfillment' => ['required', Rule::in(['sewa', 'beli'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'items.required' => 'Pilih minimal satu gear untuk paket.',
            'items.min' => 'Pilih minimal satu gear untuk paket.',
        ]);
    }
}
