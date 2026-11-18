<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesStatusRequest;
use App\Models\SalesOrder;
use App\Models\SalesPayment;
use App\Services\SalesOrderService;
use App\Services\SalesPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $orders,
        private readonly SalesPaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesOrder::class);

        $orders = SalesOrder::query()
            ->with(['customer', 'items'])
            ->when($request->filled('status'), fn ($query) => $query->where('order_status', $request->string('status')))
            ->when($request->filled('q'), fn ($query) => $query->where('order_code', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.sales.index', [
            'orders' => $orders,
            'statuses' => SalesOrderStatus::cases(),
        ]);
    }

    public function show(SalesOrder $salesOrder): View
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'items', 'payments.attempts', 'reservations']);

        return view('admin.sales.show', [
            'order' => $salesOrder,
            'statuses' => SalesOrderStatus::cases(),
            'instructions' => $this->payments->instructions(),
        ]);
    }

    public function transition(SalesStatusRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('update', $salesOrder);

        $target = SalesOrderStatus::from($request->validated('status'));

        if ($target === SalesOrderStatus::Cancelled) {
            $this->authorize('cancel', $salesOrder);
        }

        if ($target === SalesOrderStatus::Completed) {
            $this->authorize('complete', $salesOrder);
        }

        try {
            $this->orders->transition($salesOrder, $target, $request->user(), $request->validated('reason'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Status order diperbarui.');
    }

    public function verifyPayment(Request $request, SalesPayment $payment): RedirectResponse
    {
        $this->authorize('update', $payment->order);

        try {
            $this->payments->verify($payment, $request->user(), $request->input('note'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Pembayaran diverifikasi.');
    }

    public function rejectPayment(Request $request, SalesPayment $payment): RedirectResponse
    {
        $this->authorize('update', $payment->order);

        $validated = $request->validate(['note' => ['required', 'string', 'max:500']]);

        try {
            $this->payments->reject($payment, $request->user(), $validated['note']);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Pembayaran ditolak.');
    }

    public function recordCash(Request $request, SalesPayment $payment): RedirectResponse
    {
        $this->authorize('update', $payment->order);

        try {
            $this->payments->recordCash($payment, $request->user(), $request->input('note'));
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Pembayaran tunai dicatat.');
    }

    public function proof(Request $request, SalesPayment $payment): mixed
    {
        $this->authorize('view', $payment->order);

        abort_if(blank($payment->proof_path), 404);

        return response()->file(
            Storage::disk(config('gearora.payment.proof_disk'))->path($payment->proof_path)
        );
    }

    public function methods(): array
    {
        return PaymentMethod::values();
    }
}
