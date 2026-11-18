<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdditionalChargeStatus;
use App\Http\Controllers\Controller;
use App\Models\AdditionalCharge;
use App\Models\RentalOrder;
use App\Services\AdditionalChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ChargeController extends Controller
{
    public function __construct(private readonly AdditionalChargeService $charges) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AdditionalCharge::class);

        $charges = AdditionalCharge::query()
            ->with(['order.customer', 'asset'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner
                    ->where('charge_code', 'like', "%{$keyword}%")
                    ->orWhereHas('order', fn ($order) => $order->where('order_code', 'like', "%{$keyword}%")));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.charges.index', [
            'charges' => $charges,
            'statuses' => AdditionalChargeStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AdditionalCharge::class);

        $data = $request->validate([
            'rental_order_id' => ['required', 'integer', 'exists:rental_orders,id'],
            'type' => ['required', 'in:late_fee,damage,missing_accessory,lost_item,cleaning,other'],
            'reason' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'asset_id' => ['nullable', 'integer', 'exists:rental_assets,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = RentalOrder::query()->findOrFail($data['rental_order_id']);

        try {
            $charge = $this->charges->create($order, [
                'type' => $data['type'],
                'reason' => $data['reason'],
                'amount' => (int) $data['amount'],
                'asset_id' => $data['asset_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Biaya tambahan {$charge->charge_code} diajukan.");
    }

    public function approve(Request $request, AdditionalCharge $charge): RedirectResponse
    {
        $this->authorize('approve', $charge);

        try {
            $this->charges->approve($charge, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Biaya {$charge->charge_code} disetujui.");
    }

    public function reject(Request $request, AdditionalCharge $charge): RedirectResponse
    {
        $this->authorize('reject', $charge);

        $note = $request->validate(['note' => ['required', 'string', 'max:500']])['note'];

        try {
            $this->charges->reject($charge, $request->user(), $note);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Biaya {$charge->charge_code} ditolak.");
    }

    public function pay(Request $request, AdditionalCharge $charge): RedirectResponse
    {
        $this->authorize('approve', $charge);

        $amount = (int) $request->validate(['amount' => ['required', 'integer', 'min:1']])['amount'];

        try {
            $this->charges->recordPayment($charge, $amount, $request->user());
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', "Pembayaran biaya {$charge->charge_code} dicatat.");
    }
}
