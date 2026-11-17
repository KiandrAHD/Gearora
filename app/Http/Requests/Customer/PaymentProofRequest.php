<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class PaymentProofRequest extends FormRequest
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
            'proof' => [
                'required',
                'file',
                'max:'.config('gearora.payment.proof_max_kb'),
                'mimes:jpg,jpeg,png,pdf',
            ],
            'reference' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proof.mimes' => 'Bukti pembayaran harus berformat JPG, PNG, atau PDF.',
            'proof.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ];
    }
}
