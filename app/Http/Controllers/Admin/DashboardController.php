<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\RoleName;
use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Navigation\AdminNavigation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $can = fn (string $permission): bool => AdminNavigation::allows($user, $permission);

        $today = now()->toDateString();

        $orderCounts = $can('rentals.view') || $can('inventory.view')
            ? RentalOrder::query()->selectRaw('order_status, count(*) as total')->groupBy('order_status')->pluck('total', 'order_status')
            : collect();

        $stats = array_values(array_filter([
            $can('rentals.view') ? [
                'label' => 'Rental aktif',
                'value' => $orderCounts->only([RentalOrderStatus::Confirmed->value, RentalOrderStatus::ReadyForPickup->value, RentalOrderStatus::PickedUp->value])->sum(),
                'hint' => 'Order terkonfirmasi hingga sedang disewa',
            ] : null,
            $can('rentals.view') ? [
                'label' => 'Jatuh tempo hari ini',
                'value' => RentalOrder::query()->whereIn('order_status', [RentalOrderStatus::PickedUp->value, RentalOrderStatus::Confirmed->value, RentalOrderStatus::ReadyForPickup->value])->whereDate('end_date', $today)->count(),
                'hint' => 'Order dengan tanggal kembali hari ini',
            ] : null,
            $can('rentals.handover') ? [
                'label' => 'Pickup hari ini',
                'value' => RentalOrder::query()->where('order_status', RentalOrderStatus::ReadyForPickup->value)->whereDate('start_date', '<=', $today)->count(),
                'hint' => 'Siap diserahkan ke pelanggan',
            ] : null,
            $can('rentals.return') ? [
                'label' => 'Pengembalian hari ini',
                'value' => RentalOrder::query()->where('order_status', RentalOrderStatus::PickedUp->value)->whereDate('end_date', '<=', $today)->count(),
                'hint' => 'Unit yang harus diterima kembali',
            ] : null,
            $can('inspections.view') ? [
                'label' => 'Unit menunggu inspeksi',
                'value' => RentalAsset::query()->where('status', RentalAssetStatus::InspectionPending->value)->count(),
                'hint' => 'Unit hasil pengembalian belum diperiksa',
            ] : null,
            $can('rentals.view') ? [
                'label' => 'Rental overdue',
                'value' => $orderCounts->only([RentalOrderStatus::PickedUp->value])->sum() > 0
                    ? RentalOrder::query()->where('order_status', RentalOrderStatus::PickedUp->value)->whereDate('blocking_end_date', '<', $today)->count()
                    : 0,
                'hint' => 'Lewat tanggal kembali',
            ] : null,
            $can('payments.view') ? [
                'label' => 'Pembayaran menunggu',
                'value' => Payment::query()->where('status', PaymentStatus::WaitingVerification->value)->count(),
                'hint' => 'Menunggu verifikasi admin',
            ] : null,
            $can('sales.view') ? [
                'label' => 'Pesanan retail aktif',
                'value' => SalesOrder::query()->whereNotIn('order_status', [SalesOrderStatus::Completed->value, SalesOrderStatus::Cancelled->value, SalesOrderStatus::Expired->value])->count(),
                'hint' => 'Belum selesai diproses',
            ] : null,
            $can('inventory.view') ? [
                'label' => 'Unit perawatan/rusak',
                'value' => RentalAsset::query()->whereIn('status', [RentalAssetStatus::Maintenance->value, RentalAssetStatus::Damaged->value])->count(),
                'hint' => 'Unit yang tidak dapat disewakan',
            ] : null,
        ]));

        $lowStock = $can('inventory.view')
            ? Product::query()
                ->where('type', '!=', ProductType::Rental->value)
                ->with(['variants' => fn ($query) => $query->where('is_active', true)])
                ->where('status', ProductStatus::Active->value)
                ->take(50)->get()
                ->filter(fn (Product $product) => $product->variants->sum('stock_quantity') <= 3)
                ->take(5)
            : collect();

        return view('admin.dashboard', [
            'stats' => $stats,
            'finance' => $this->financialSummary($user),
            'financeLabels' => ['rental' => 'Rental revenue', 'sales' => 'Retail sales', 'payments' => 'Payment collected', 'deposits' => 'Deposit held', 'charges' => 'Outstanding charges'],
            'quickActions' => $this->quickActions($user),
            'canRentals' => $can('rentals.view'),
            'canPayments' => $can('payments.view'),
            'canInventory' => $can('inventory.view'),
            'canTotals' => $can('products.view') || $can('inventory.view'),
            'recentOrders' => $can('rentals.view')
                ? RentalOrder::query()->with(['customer', 'latestPayment'])->latest()->take(6)->get()
                : collect(),
            'pendingPayments' => $can('payments.view')
                ? Payment::query()->with(['order.customer'])->where('status', PaymentStatus::WaitingVerification->value)->latest('proof_submitted_at')->take(5)->get()
                : collect(),
            'lowStock' => $lowStock,
            'activities' => $can('dashboard.view') ? ActivityLog::query()->with('user')->latest('created_at')->take(8)->get() : collect(),
            'totals' => [
                'categories' => Category::query()->count(),
                'products' => Product::query()->count(),
                'assets' => RentalAsset::query()->count(),
                'customers' => User::query()->whereHas('roles', fn ($query) => $query->where('name', RoleName::Customer->value))->count(),
            ],
        ]);
    }

    /**
     * Ringkasan keuangan periode berjalan, hanya untuk grup yang diizinkan.
     *
     * @return array<string, array<string, mixed>>
     */
    private function financialSummary(?User $user): array
    {
        $permissions = [
            'rental' => 'reports.rentals.view', 'sales' => 'reports.sales.view', 'payments' => 'reports.payments.view',
            'deposits' => 'reports.deposits.view', 'charges' => 'reports.charges.view',
        ];

        $allowed = array_values(array_filter(
            array_keys($permissions),
            fn (string $group) => AdminNavigation::allows($user, $permissions[$group]),
        ));

        if ($allowed === []) {
            return [];
        }

        $start = CarbonImmutable::now(config('app.timezone'))->startOfMonth();
        $end = CarbonImmutable::now(config('app.timezone'))->endOfDay();
        $filters = $this->reports->filters($start, $end);

        return $this->reports->dashboard($filters, $allowed);
    }

    /**
     * @return array<int, array{label:string,route:string,permission:string}>
     */
    private function quickActions(?User $user): array
    {
        return array_values(array_filter([
            ['label' => 'Verifikasi pembayaran', 'route' => 'admin.payments.index', 'permission' => 'payments.view'],
            ['label' => 'Proses pickup', 'route' => 'admin.operations.pickups', 'permission' => 'rentals.handover'],
            ['label' => 'Proses pengembalian', 'route' => 'admin.operations.returns', 'permission' => 'rentals.return'],
            ['label' => 'Lihat overdue', 'route' => 'admin.operations.overdue', 'permission' => 'rentals.view'],
            ['label' => 'Proses pesanan retail', 'route' => 'admin.sales.index', 'permission' => 'sales.view'],
            ['label' => 'Kelola produk', 'route' => 'admin.products.index', 'permission' => 'products.view'],
            ['label' => 'Lihat laporan', 'route' => 'admin.reports.index', 'any' => AdminNavigation::reportPermissions()],
        ], fn (array $action) => isset($action['any'])
            ? ($user !== null && ($user->isOwner() || $user->hasAnyPermission($action['any'])))
            : AdminNavigation::allows($user, $action['permission'] ?? null), ));
    }
}
