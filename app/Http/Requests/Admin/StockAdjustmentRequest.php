<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockAdjustmentRequest extends FormRequest
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
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'type' => ['required', Rule::in([
                StockMovementType::Initial->value,
                StockMovementType::Addition->value,
                StockMovementType::Adjustment->value,
                StockMovementType::Damage->value,
                StockMovementType::Lost->value,
            ])],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
