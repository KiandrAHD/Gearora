<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\RentalOrder;
use App\Services\RefundCalculationService;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refunds,
        private readonly RefundCalculationService $calculator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Refund::class);

        $refunds = Refund::query()
            ->with(['order.customer', 'payment'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner
                    ->where('refund_code', 'like', "%{$keyword}%")
                    ->orWhereHas('order', fn ($order) => $order
                        ->where('order_code', 'like', "%{$keyword}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$keyword}%"))));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'statuses' => RefundStatus::cases(),
        ]);
    }

    /** Ajukan refund berdasarkan hasil perhitungan kebijakan pembatalan. */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Refund::class);

        $data = $request->validate([
            'rental_order_id' => ['required', 'integer', 'exists:rental_orders,id'],
            'method' => ['nullable', 'in:gateway,manual_transfer,cash,deposit_offset'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $order = RentalOrder::query()->findOrFail($data['rental_order_id']);

        $method = isset($data['method']) ? RefundMethod::from($data['method']) : RefundMethod::ManualTransfer;

        try {
            $refund = $this->refunds->request(
                $order,
                $request->user(),
                $this->calculator->calculate($order),
                $method,
                $data['reason'] ?? null,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Refund {$refund->refund_code} diajukan.");
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('approve', $refund);

        try {
            $this->refunds->approve($refund, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Refund {$refund->refund_code} disetujui.");
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('approve', $refund);

        $note = $request->validate(['note' => ['required', 'string', 'max:500']])['note'];

        try {
            $this->refunds->reject($refund, $request->user(), $note);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Refund {$refund->refund_code} ditolak.");
    }

    public function process(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('process', $refund);

        try {
            $this->refunds->process($refund, $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Refund gagal diproses: '.$exception->getMessage());
        }

        return back()->with('status', "Refund {$refund->refund_code} diproses.");
    }

    /** Selesaikan refund manual dengan bukti transfer pengembalian dana. */
    public function complete(Request $request, Refund $refund): RedirectResponse
    {
        $this->authorize('process', $refund);

        $data = $request->validate([
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $disk = config('gearora.payment.proof_disk');
        $path = $data['evidence']->store('refund-evidence/'.$refund->rental_order_id, ['disk' => $disk]);

        try {
            $this->refunds->completeManual($refund, $request->user(), $path, $data['note'] ?? null);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Refund {$refund->refund_code} diselesaikan.");
    }
}
