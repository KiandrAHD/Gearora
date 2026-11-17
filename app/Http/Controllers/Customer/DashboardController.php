<?php

namespace App\Http\Controllers\Customer;

use App\Enums\RentalOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\RentalOrder;
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

        $stats = [
            'active' => RentalOrder::query()->forCustomer($user->getKey())
                ->whereIn('order_status', [
                    RentalOrderStatus::PendingPayment->value,
                    RentalOrderStatus::PaymentReview->value,
                    RentalOrderStatus::Confirmed->value,
                    RentalOrderStatus::ReadyForPickup->value,
                    RentalOrderStatus::PickedUp->value,
                    RentalOrderStatus::Returned->value,
                ])->count(),
            'waiting_payment' => RentalOrder::query()->forCustomer($user->getKey())
                ->whereIn('order_status', [
                    RentalOrderStatus::PendingPayment->value,
                    RentalOrderStatus::PaymentReview->value,
                ])->count(),
            'completed' => RentalOrder::query()->forCustomer($user->getKey())
                ->where('order_status', RentalOrderStatus::Completed->value)
                ->count(),
        ];

        return view('customer.dashboard', [
            'orders' => $orders,
            'stats' => $stats,
        ]);
    }
}
