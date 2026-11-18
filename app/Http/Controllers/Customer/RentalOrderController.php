<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Enums\RentalOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\RentalAvailabilityException;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Services\PaymentService;
use App\Services\RentalOrderService;
use App\Support\RentalPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class RentalOrderController extends Controller
{
    private const DRAFT_KEY = 'rental_booking_draft';

    public function __construct(
        private readonly RentalOrderService $orders,
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $orders = RentalOrder::query()
            ->with(['items', 'latestPayment'])
            ->forCustomer($request->user()->getKey())
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    public function show(Request $request, RentalOrder $order): View
    {
        $this->authorize('view', $order);

        $order->load(['items.assignments.asset', 'payments.verifier', 'customer']);

        return view('customer.orders.show', [
            'order' => $order,
            'instructions' => $this->payments->instructions(),
            'hasTransferInstruction' => $this->payments->hasTransferInstruction(),
            'gatewayAvailable' => $this->payments->gatewayAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $draft = $request->session()->get(self::DRAFT_KEY);

        if (! is_array($draft)) {
            return redirect()->route('catalog.index')
                ->with('status', 'Pilih produk dan tanggal terlebih dahulu sebelum memesan.');
        }

        try {
            $period = RentalPeriod::from($draft['start_date'], $draft['end_date']);

            $order = $this->orders->create(
                customer: $request->user(),
                requestedItems: [[
                    'variant_id' => (int) $draft['variant_id'],
                    'quantity' => (int) $draft['quantity'],
                ]],
                period: $period,
                method: PaymentMethod::from($draft['method'] ?? PaymentMethod::Transfer->value),
                notes: $draft['notes'] ?? null,
            );
        } catch (RentalAvailabilityException|InvalidArgumentException $exception) {
            return redirect()->route('booking.summary')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('booking.summary')
                ->with('error', 'Order gagal dibuat. Silakan coba lagi atau hubungi admin.');
        }

        $request->session()->forget(self::DRAFT_KEY);
        $request->session()->regenerateToken();

        return redirect()->route('customer.orders.show', $order)
            ->with('status', "Order {$order->order_code} berhasil dibuat. Selesaikan pembayaran sesuai instruksi.");
    }

    public function cancel(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->authorize('view', $order);

        if (! in_array($order->order_status, [RentalOrderStatus::PendingPayment, RentalOrderStatus::PaymentReview], true)) {
            return back()->with('error', 'Order ini tidak dapat dibatalkan karena sudah diproses.');
        }

        try {
            $this->orders->transition(
                $order,
                RentalOrderStatus::Cancelled,
                $request->user(),
                'Dibatalkan oleh pelanggan.',
            );
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Order dibatalkan.');
    }
}
