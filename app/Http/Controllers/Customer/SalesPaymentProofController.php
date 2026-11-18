<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\SalesPayment;
use App\Services\SalesPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SalesPaymentProofController extends Controller
{
    public function __construct(private readonly SalesPaymentService $payments) {}

    public function store(Request $request, SalesPayment $payment): RedirectResponse
    {
        $this->authorize('view', $payment->order);

        $validated = $request->validate([
            'proof' => ['required', 'file', 'max:'.(int) config('gearora.payment.proof_max_kb', 5120), 'mimes:jpg,jpeg,png,pdf'],
            'reference' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payments->submitProof($payment, $validated['proof'], $validated['reference'] ?? null, $validated['note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Bukti pembayaran diunggah. Menunggu verifikasi admin.');
    }
}
