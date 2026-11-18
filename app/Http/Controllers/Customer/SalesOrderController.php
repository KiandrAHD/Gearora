<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentMethod;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use App\Services\SalesPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $orders = SalesOrder::query()
            ->where('customer_id', $request->user()->getKey())
            ->with(['items'])
            ->latest()
            ->paginate(10);

        return view('customer.sales.index', ['orders' => $orders]);
    }

    public function show(Request $request, SalesOrder $salesOrder): View
    {
        $this->authorize('view', $salesOrder);

        $salesOrder->load(['items', 'payments.attempts', 'reservations']);

        return view('customer.sales.show', [
            'order' => $salesOrder,
            'instructions' => $this->payments->instructions(),
            'gatewayAvailable' => $this->payments->gatewayAvailable(),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function cancel(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('cancel', $salesOrder);

        if (in_array($salesOrder->order_status, [SalesOrderStatus::Completed, SalesOrderStatus::Cancelled, SalesOrderStatus::Expired], true)) {
            return back()->with('error', 'Pesanan ini sudah final.');
        }

        try {
            $this->orders->cancel($salesOrder, $request->user(), 'Dibatalkan oleh pelanggan.');
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Pesanan dibatalkan.');
    }
}
