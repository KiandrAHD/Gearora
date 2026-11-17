<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('permission', PermissionName::CustomersView->value);

        $customers = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::Customer->value))
            ->withCount('rentalOrders')
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = $request->string('q')->toString();
                $query->where(fn ($inner) => $inner->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%"));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function show(User $customer): View
    {
        $this->authorize('permission', PermissionName::CustomersView->value);

        abort_unless($customer->hasCustomerRole(), 404);

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => RentalOrder::query()
                ->with(['items', 'latestPayment'])
                ->forCustomer($customer->getKey())
                ->latest()
                ->paginate(10),
            'activities' => ActivityLog::query()
                ->where('subject_type', $customer->getMorphClass())
                ->where('subject_id', $customer->getKey())
                ->latest('created_at')
                ->take(10)
                ->get(),
        ]);
    }
}
