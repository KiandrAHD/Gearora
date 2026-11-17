<?php

namespace App\Http\Requests\Customer;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RentalOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'method' => ['required', Rule::in(PaymentMethod::values())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, array{variant_id:int, quantity:int}>
     */
    public function items(): array
    {
        return array_map(fn (array $item) => [
            'variant_id' => (int) $item['variant_id'],
            'quantity' => (int) $item['quantity'],
        ], $this->validated('items'));
    }
}
