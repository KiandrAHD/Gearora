<?php

namespace App\Http\Requests\Admin;

use App\Enums\SalesOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(SalesOrderStatus::values())],
            'reason' => ['nullable', 'string', 'max:500', 'required_if:status,'.SalesOrderStatus::Cancelled->value],
        ];
    }
}
