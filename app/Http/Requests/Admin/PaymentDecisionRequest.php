<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PaymentDecisionRequest extends FormRequest
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
            'decision' => ['required', Rule::in([PaymentStatus::Verified->value, PaymentStatus::Rejected->value])],
            'note' => ['nullable', 'string', 'max:500'],
            'note_required' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->sometimes('note', ['required', 'string', 'min:3'], function () {
            return $this->input('decision') === PaymentStatus::Rejected->value;
        });
    }
}
