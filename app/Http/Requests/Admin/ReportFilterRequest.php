<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'start_date' => $this->filled('start_date') ? $this->input('start_date') : now()->startOfMonth()->toDateString(),
            'end_date' => $this->filled('end_date') ? $this->input('end_date') : now()->toDateString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', 'max:32'],
            'transaction_type' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', 'string', Rule::in(['rental', 'sales', 'all'])],
            'method' => ['nullable', 'string', Rule::in(PaymentMethod::values())],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    /**
     * Opsi filter yang lolos validasi, dipakai seragam oleh layar dan ekspor.
     *
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return [
            'status' => $this->validated('status'),
            'type' => $this->validated('type'),
            'method' => $this->validated('method'),
            'product_id' => $this->validated('product_id'),
            'category_id' => $this->validated('category_id'),
            'transaction_type' => $this->validated('transaction_type'),
        ];
    }

    /** Rentang tanggal dalam timezone aplikasi (Asia/Jakarta). */
    public function start(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('start_date'), config('app.timezone'))->startOfDay();
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('end_date'), config('app.timezone'))->endOfDay();
    }
}
