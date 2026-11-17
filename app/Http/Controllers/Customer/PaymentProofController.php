<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\PaymentProofRequest;
use App\Models\RentalOrder;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class PaymentProofController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(PaymentProofRequest $request, RentalOrder $order): RedirectResponse
    {
        $this->authorize('view', $order);

        $payment = $order->payments()
            ->where('method', PaymentMethod::Transfer->value)
            ->latest()
            ->first();

        if ($payment === null) {
            return back()->with('error', 'Order ini tidak memakai metode transfer bank.');
        }

        try {
            $this->payments->submitProof(
                $payment,
                $request->file('proof'),
                $request->string('reference')->toString() ?: null,
                $request->string('note')->toString() ?: null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Bukti pembayaran terkirim. Admin akan memverifikasi pembayaranmu.');
    }
}
