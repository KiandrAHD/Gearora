<?php

namespace App\Http\Requests\Admin;

use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Enums\TripSpecialNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TripRecommendationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'required_special_needs' => array_values(array_filter((array) $this->input('required_special_needs', []))),
            'quantity_per_participant' => $this->boolean('quantity_per_participant'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'activity' => ['required', 'string', Rule::in(TripActivity::ruleValues())],
            'min_duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'max_duration_days' => ['nullable', 'integer', 'min:1', 'max:365', 'gte:min_duration_days'],
            'min_participants' => ['required', 'integer', 'min:1', 'max:1000'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000', 'gte:min_participants'],
            'required_special_needs' => ['nullable', 'array'],
            'required_special_needs.*' => ['string', Rule::in(TripSpecialNeed::values())],
            'product_category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'service_mode' => ['required', 'string', Rule::in(TripServiceMode::values())],
            'recommended_quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'quantity_per_participant' => ['boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:10000'],
            'reason_template' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->filled('product_category_id') && ! $this->filled('product_id') && ! $this->filled('product_variant_id')) {
                $validator->errors()->add('product_id', 'Tentukan minimal satu: kategori, produk, atau varian.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'max_duration_days.gte' => 'Durasi maksimum tidak boleh lebih kecil dari durasi minimum.',
            'max_participants.gte' => 'Peserta maksimum tidak boleh lebih kecil dari peserta minimum.',
            'activity.in' => 'Aktivitas tidak dikenal.',
        ];
    }
}
