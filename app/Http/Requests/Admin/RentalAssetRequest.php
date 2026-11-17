<?php

namespace App\Http\Requests\Admin;

use App\Enums\RentalAssetCondition;
use App\Enums\RentalAssetStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RentalAssetRequest extends FormRequest
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
        $asset = $this->route('asset');
        $assetId = $asset?->getKey();

        return [
            'asset_code' => [
                'required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('rental_assets', 'asset_code')->ignore($assetId),
            ],
            'condition' => ['required', Rule::in(RentalAssetCondition::values())],
            'status' => ['required', Rule::in(RentalAssetStatus::values())],
            'purchase_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
