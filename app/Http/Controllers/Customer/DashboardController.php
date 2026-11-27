<?php

namespace App\Http\Controllers\Customer;

use App\Enums\RentalOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = RentalOrder::query()
            ->with(['items', 'latestPayment'])
            ->forCustomer($user->getKey())
            ->latest()
            ->take(5)
            ->get();

        $base = fn () => RentalOrder::query()->forCustomer($user->getKey());

        $stats = [
            'active' => $base()
                ->whereIn('order_status', [
                    RentalOrderStatus::Confirmed->value,
                    RentalOrderStatus::ReadyForPickup->value,
                    RentalOrderStatus::PickedUp->value,
                    RentalOrderStatus::Returned->value,
                ])->count(),
            'waiting_payment' => $base()
                ->whereIn('order_status', [
                    RentalOrderStatus::PendingPayment->value,
                    RentalOrderStatus::PaymentReview->value,
                ])->count(),
            'completed' => $base()
                ->where('order_status', RentalOrderStatus::Completed->value)
                ->count(),
            'sales_total' => SalesOrder::query()
                ->where('customer_id', $user->getKey())
                ->whereNotIn('order_status', [SalesOrderStatus::Cancelled->value])
                ->count(),
        ];

        return view('customer.dashboard', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
