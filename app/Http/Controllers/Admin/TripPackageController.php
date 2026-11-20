<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripPackageStatus;
use App\Http\Controllers\Controller;
use App\Models\TripPackage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pemantauan paket Trip Planner. Semua status dan total diturunkan dari child
 * order (rental/sales); tidak ada state machine atau nominal kedua di sini.
 */
class TripPackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TripPackage::class);

        $packages = TripPackage::query()
            ->with([
                'customer',
                'rentalOrder.latestPayment',
                'salesOrder.latestPayment',
            ])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $needle = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('code', 'like', $needle)
                        ->orWhere('destination', 'like', $needle)
                        ->orWhereHas('customer', fn ($customer) => $customer
                            ->where('name', 'like', $needle)
                            ->orWhere('email', 'like', $needle));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.trip-packages.index', [
            'packages' => $packages,
            'statuses' => TripPackageStatus::cases(),
        ]);
    }

    public function show(TripPackage $tripPackage): View
    {
        $this->authorize('view', $tripPackage);

        $tripPackage->load([
            'customer',
            'rentalOrder.items',
            'rentalOrder.payments',
            'rentalOrder.latestPayment',
            'salesOrder.items',
            'salesOrder.payments',
            'salesOrder.latestPayment',
        ]);

        return view('admin.trip-packages.show', ['package' => $tripPackage]);
    }
}
