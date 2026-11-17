<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentDecisionRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['order.customer', 'verifier'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->whereHas('order', fn ($order) => $order
                    ->where('order_code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
        ]);
    }

    public function decide(PaymentDecisionRequest $request, Payment $payment): RedirectResponse
    {
        if ($request->string('decision')->toString() === PaymentStatus::Verified->value) {
            $this->authorize('verify', $payment);

            $this->payments->verify($payment, $request->user(), $request->string('note')->toString() ?: null);

            return back()->with('status', 'Pembayaran diverifikasi.');
        }

        $this->authorize('reject', $payment);

        $this->payments->reject($payment, $request->user(), $request->string('note')->toString());

        return back()->with('status', 'Pembayaran ditolak.');
    }

    public function recordCash(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('verify', $payment);

        $note = $request->validate(['note' => ['nullable', 'string', 'max:255']])['note'] ?? null;

        $this->payments->recordCash($payment, $request->user(), $note);

        return back()->with('status', 'Pembayaran tunai dicatat sebagai diterima.');
    }

    /**
     * Bukti pembayaran disimpan di private storage dan hanya dapat diunduh
     * oleh pemilik order atau staf yang memiliki izin.
     */
    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        abort_if(blank($payment->proof_path), 404);

        $disk = config('gearora.payment.proof_disk');

        abort_unless(Storage::disk($disk)->exists($payment->proof_path), 404);

        return Storage::disk($disk)->download(
            $payment->proof_path,
            'bukti-'.$payment->order->order_code.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION),
        );
    }
}
