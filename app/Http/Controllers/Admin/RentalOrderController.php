<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RentalOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Services\RentalOrderService;
use App\Services\RentalOverdueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RentalOrderController extends Controller
{
    public function __construct(private readonly RentalOrderService $orders) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', RentalOrder::class);

        $orders = RentalOrder::query()
            ->with(['customer', 'items', 'latestPayment'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner->where('order_code', 'like', "%{$keyword}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$keyword}%")->orWhere('email', 'like', "%{$keyword}%")));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('order_status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('start_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('start_date', '<=', $request->date('to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.rentals.index', [
            'orders' => $orders,
            'statuses' => RentalOrderStatus::cases(),
        ]);
    }

    public function show(RentalOrder $order): View
    {
        $this->authorize('view', $order);

        $order->load([
            'customer',
            'items.assignments.asset.variant',
            'payments.verifier',
        ]);

        return view('admin.rentals.show', [
            'order' => $order,
            'statuses' => $order->order_status->manualTransitions(),
            'overdue' => app(RentalOverdueService::class)->forOrder($order),
        ]);
    }

    public function transition(Request $request, RentalOrder $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($status) => $status->value, $order->order_status->manualTransitions()))],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->orders->transition(
                $order,
                RentalOrderStatus::from($data['status']),
                $request->user(),
                $data['reason'] ?? null,
            );
        } catch (InvalidOrderTransitionException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Status order diperbarui.');
    }
}
