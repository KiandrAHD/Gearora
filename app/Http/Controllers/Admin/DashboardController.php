<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();

        $orderCounts = RentalOrder::query()
            ->selectRaw('order_status, count(*) as total')
            ->groupBy('order_status')
            ->pluck('total', 'order_status');

        $stats = [
            [
                'label' => 'Rental aktif',
                'value' => $orderCounts->only([
                    RentalOrderStatus::Confirmed->value,
                    RentalOrderStatus::ReadyForPickup->value,
                    RentalOrderStatus::PickedUp->value,
                ])->sum(),
                'hint' => 'Order terkonfirmasi hingga sedang disewa',
            ],
            [
                'label' => 'Menunggu pembayaran',
                'value' => $orderCounts->only([
                    RentalOrderStatus::PendingPayment->value,
                    RentalOrderStatus::PaymentReview->value,
                ])->sum(),
                'hint' => 'Termasuk bukti yang belum diverifikasi',
            ],
            [
                'label' => 'Jatuh tempo hari ini',
                'value' => RentalOrder::query()
                    ->whereIn('order_status', [
                        RentalOrderStatus::PickedUp->value,
                        RentalOrderStatus::Confirmed->value,
                        RentalOrderStatus::ReadyForPickup->value,
                    ])
                    ->whereDate('end_date', $today)
                    ->count(),
                'hint' => 'Order dengan tanggal kembali hari ini',
            ],
            [
                'label' => 'Unit perawatan/rusak',
                'value' => RentalAsset::query()
                    ->whereIn('status', [
                        RentalAssetStatus::Maintenance->value,
                        RentalAssetStatus::Damaged->value,
                    ])->count(),
                'hint' => 'Unit yang tidak dapat disewakan',
            ],
        ];

        $lowStock = Product::query()
            ->where('type', '!=', ProductType::Rental->value)
            ->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->where('status', ProductStatus::Active->value)
            ->take(50)
            ->get()
            ->filter(fn (Product $product) => $product->variants->sum('stock_quantity') <= 3)
            ->take(5);

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentOrders' => RentalOrder::query()
                ->with(['customer', 'latestPayment'])
                ->latest()
                ->take(6)
                ->get(),
            'pendingPayments' => Payment::query()
                ->with(['order.customer'])
                ->where('status', PaymentStatus::WaitingVerification->value)
                ->latest('proof_submitted_at')
                ->take(5)
                ->get(),
            'lowStock' => $lowStock,
            'activities' => ActivityLog::query()->with('user')->latest('created_at')->take(8)->get(),
            'totals' => [
                'categories' => Category::query()->count(),
                'products' => Product::query()->count(),
                'assets' => RentalAsset::query()->count(),
                'customers' => User::query()->whereHas('roles', fn ($query) => $query->where('name', RoleName::Customer->value))->count(),
            ],
        ]);
    }
}
