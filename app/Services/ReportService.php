<?php

namespace App\Services;

use App\Enums\AdditionalChargeStatus;
use App\Enums\AdditionalChargeType;
use App\Enums\DepositTransactionType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\RentalAssetStatus;
use App\Enums\RentalOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Enums\StockMovementType;
use App\Models\AdditionalCharge;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RentalAsset;
use App\Models\RentalOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesPayment;
use App\Models\SalesStockReservation;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Sumber tunggal angka laporan. Seluruh nilai berasal dari transaksi sumber;
 * tidak ada dummy data. Definisi metrik: .docs/REPORT_DEFINITIONS.md.
 */
class ReportService
{
    /** @return array<string, mixed> */
    public function filters(CarbonImmutable $start, CarbonImmutable $end, array $options = []): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => $options['status'] ?? null,
            'type' => $options['type'] ?? 'all',
            'method' => $options['method'] ?? null,
            'product_id' => $options['product_id'] ?? null,
            'category_id' => $options['category_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array<string, mixed>
     */
    public function dashboard(array $f): array
    {
        $rental = $this->rentalRevenue($f);
        $sales = $this->retailSales($f);
        $payments = $this->payments($f);
        $deposits = $this->deposits($f);
        $charges = $this->additionalCharges($f);
        $inventory = $this->inventory($f);
        $operations = $this->rentalOperations($f);

        return [
            'rental' => $rental['metrics'],
            'sales' => $sales['metrics'],
            'payments' => $payments['metrics'],
            'deposits' => $deposits['metrics'],
            'charges' => $charges['metrics'],
            'inventory' => $inventory['metrics'],
            'operations' => $operations['metrics'],
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,int|float>,table:array<int,array<string,mixed>>}
     */
    public function rentalRevenue(array $f): array
    {
        $paymentQuery = Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereBetween('settled_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->where('method', $method));

        $rentalGross = (int) $paymentQuery->clone()->sum('rental_amount');
        $depositCollected = (int) $paymentQuery->clone()->sum('deposit_amount');

        $refundQuery = Refund::query()
            ->where('status', RefundStatus::Succeeded->value)
            ->whereBetween('processed_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->whereHas('payment', fn ($p) => $p->where('method', $method)));

        $rentalRefund = (int) $refundQuery->clone()->sum('rental_amount');

        $orders = RentalOrder::query()
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->when($f['status'], fn ($query, $status) => $query->where('order_status', $status));

        $orderCount = (clone $orders)->count();
        $completed = (clone $orders)->where('order_status', RentalOrderStatus::Completed->value)->count();
        $cancelled = (clone $orders)->where('order_status', RentalOrderStatus::Cancelled->value)->count();
        $subtotal = (int) (clone $orders)->sum('subtotal');

        $table = (clone $orders)
            ->with(['customer', 'payments'])
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->map(function (RentalOrder $order) {
                $paidRental = $order->payments
                    ->where('status', PaymentStatus::Verified)
                    ->sum(fn ($payment) => (int) $payment->rental_amount);

                return [
                    'order_code' => $order->order_code,
                    'customer' => $order->customer?->name,
                    'start_date' => $order->start_date?->toDateString(),
                    'end_date' => $order->end_date?->toDateString(),
                    'duration_days' => (int) $order->duration_days,
                    'status' => $order->order_status->value,
                    'subtotal' => (int) $order->subtotal,
                    'deposit_total' => (int) $order->deposit_total,
                    'total' => (int) $order->total,
                    'rental_paid' => $paidRental,
                ];
            })
            ->all();

        return [
            'metrics' => [
                'rental_subtotal' => $subtotal,
                'rental_gross' => $rentalGross,
                'rental_refund' => $rentalRefund,
                'net_rental_revenue' => $rentalGross - $rentalRefund,
                'deposit_collected' => $depositCollected,
                'order_count' => $orderCount,
                'completed_orders' => $completed,
                'cancelled_orders' => $cancelled,
                'average_order_value' => $orderCount > 0 ? (int) round($subtotal / $orderCount) : 0,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,mixed>,table:array<int,array<string,mixed>>}
     */
    public function retailSales(array $f): array
    {
        $orders = SalesOrder::query()
            ->where('payment_status', PaymentStatus::Verified->value)
            ->whereNotNull('confirmed_at')
            ->whereBetween('confirmed_at', [$f['start'], $f['end']])
            ->when($f['status'], fn ($query, $status) => $query->where('order_status', $status));

        $orderCount = (clone $orders)->count();
        $gross = (int) (clone $orders)->sum('subtotal');
        $discounts = (int) (clone $orders)->sum('discount_total');
        $net = (int) (clone $orders)->sum('total');

        $itemsSold = (int) SalesOrderItem::query()
            ->whereIn('sales_order_id', (clone $orders)->select('id'))
            ->sum('quantity');

        $topProducts = SalesOrderItem::query()
            ->whereIn('sales_order_id', (clone $orders)->select('id'))
            ->selectRaw('product_name_snapshot, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('product_name_snapshot')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product_name_snapshot,
                'quantity' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        $topVariants = SalesOrderItem::query()
            ->whereIn('sales_order_id', (clone $orders)->select('id'))
            ->selectRaw('product_name_snapshot, variant_name_snapshot, sku_snapshot, SUM(quantity) as qty, SUM(line_total) as revenue')
            ->groupBy('product_name_snapshot', 'variant_name_snapshot', 'sku_snapshot')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'product' => $row->product_name_snapshot,
                'variant' => $row->variant_name_snapshot,
                'sku' => $row->sku_snapshot,
                'quantity' => (int) $row->qty,
                'revenue' => (int) $row->revenue,
            ])
            ->all();

        $table = (clone $orders)
            ->with(['customer', 'items'])
            ->latest('confirmed_at')
            ->limit(500)
            ->get()
            ->map(fn (SalesOrder $order) => [
                'order_code' => $order->order_code,
                'customer' => $order->customer?->name,
                'confirmed_at' => $order->confirmed_at?->toDateTimeString(),
                'status' => $order->order_status->value,
                'subtotal' => (int) $order->subtotal,
                'discount_total' => (int) $order->discount_total,
                'total' => (int) $order->total,
                'items' => (int) $order->items->sum('quantity'),
            ])
            ->all();

        return [
            'metrics' => [
                'gross_sales' => $gross,
                'discount_total' => $discounts,
                'net_sales' => $net,
                'refunded_sales' => 0,
                'order_count' => $orderCount,
                'items_sold' => $itemsSold,
                'average_order_value' => $orderCount > 0 ? (int) round($net / $orderCount) : 0,
                'top_products' => $topProducts,
                'top_variants' => $topVariants,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,int|array<string,int>>,table:array<int,array<string,mixed>>}
     */
    public function payments(array $f): array
    {
        $rental = Payment::query()
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->where('method', $method));

        $sales = SalesPayment::query()
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->where('method', $method));

        $byMethod = [];
        foreach (PaymentMethod::cases() as $method) {
            $byMethod[$method->value] = (int) (clone $rental)->where('method', $method->value)->sum('amount')
                + (int) (clone $sales)->where('method', $method->value)->sum('amount');
        }

        $byStatus = [];
        foreach ([
            PaymentStatus::Verified, PaymentStatus::WaitingVerification, PaymentStatus::Processing,
            PaymentStatus::Failed, PaymentStatus::Expired, PaymentStatus::Refunded, PaymentStatus::PartiallyRefunded,
            PaymentStatus::Unpaid, PaymentStatus::Rejected,
        ] as $status) {
            $byStatus[$status->value] = (clone $rental)->where('status', $status->value)->count()
                + (clone $sales)->where('status', $status->value)->count();
        }

        $collected = (int) Payment::query()
            ->where('status', PaymentStatus::Verified->value)
            ->whereBetween('settled_at', [$f['start'], $f['end']])
            ->sum('amount')
            + (int) SalesPayment::query()
                ->where('status', PaymentStatus::Verified->value)
                ->whereBetween('settled_at', [$f['start'], $f['end']])
                ->sum('amount');

        $table = [];

        Payment::query()
            ->with(['order.customer'])
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->where('method', $method))
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->each(function (Payment $payment) use (&$table) {
                $table[] = [
                    'type' => 'rental',
                    'reference' => $payment->order?->order_code,
                    'customer' => $payment->order?->customer?->name,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'amount' => (int) $payment->amount,
                    'created_at' => $payment->created_at?->toDateTimeString(),
                    'settled_at' => $payment->settled_at?->toDateTimeString(),
                ];
            });

        SalesPayment::query()
            ->with(['order.customer'])
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->when($f['method'], fn ($query, $method) => $query->where('method', $method))
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->each(function (SalesPayment $payment) use (&$table) {
                $table[] = [
                    'type' => 'sales',
                    'reference' => $payment->order?->order_code,
                    'customer' => $payment->order?->customer?->name,
                    'method' => $payment->method->value,
                    'status' => $payment->status->value,
                    'amount' => (int) $payment->amount,
                    'created_at' => $payment->created_at?->toDateTimeString(),
                    'settled_at' => $payment->settled_at?->toDateTimeString(),
                ];
            });

        usort($table, fn (array $a, array $b) => strcmp((string) $b['created_at'], (string) $a['created_at']));

        return [
            'metrics' => [
                'by_method' => $byMethod,
                'by_status' => $byStatus,
                'collected' => $collected,
            ],
            'table' => array_slice($table, 0, 500),
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,int|array<string,int>>,table:array<int,array<string,mixed>>}
     */
    public function deposits(array $f): array
    {
        $inRange = Deposit::query()->whereBetween('created_at', [$f['start'], $f['end']]);

        $required = (int) (clone $inRange)->sum('required_amount');
        $collected = (int) (clone $inRange)->sum('collected_amount');
        $deducted = (int) (clone $inRange)->sum('deducted_amount');
        $refunded = (int) (clone $inRange)->sum('refunded_amount');
        $forfeited = (int) (clone $inRange)->sum('forfeited_amount');
        $outstanding = (int) (clone $inRange)->sum('outstanding_amount');

        $held = (int) Deposit::query()
            ->selectRaw('COALESCE(SUM(collected_amount - deducted_amount - refunded_amount - forfeited_amount), 0) as balance')
            ->value('balance');

        $flows = [];
        foreach (DepositTransactionType::cases() as $type) {
            $flows[$type->value] = (int) DepositTransaction::query()
                ->where('type', $type->value)
                ->whereBetween('created_at', [$f['start'], $f['end']])
                ->sum('amount');
        }

        $table = (clone $inRange)
            ->with(['order', 'customer'])
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->map(fn (Deposit $deposit) => [
                'order_code' => $deposit->order?->order_code,
                'customer' => $deposit->customer?->name,
                'required' => (int) $deposit->required_amount,
                'collected' => (int) $deposit->collected_amount,
                'deducted' => (int) $deposit->deducted_amount,
                'refunded' => (int) $deposit->refunded_amount,
                'forfeited' => (int) $deposit->forfeited_amount,
                'balance' => $deposit->balance(),
                'status' => $deposit->status->value,
            ])
            ->all();

        return [
            'metrics' => [
                'required' => $required,
                'collected' => $collected,
                'held_balance' => $held,
                'deducted' => $deducted,
                'refunded' => $refunded,
                'forfeited' => $forfeited,
                'outstanding' => $outstanding,
                'flows' => $flows,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,mixed>,table:array<int,array<string,mixed>>}
     */
    public function additionalCharges(array $f): array
    {
        $query = AdditionalCharge::query()->whereBetween('created_at', [$f['start'], $f['end']]);

        $byType = [];
        foreach (AdditionalChargeType::cases() as $type) {
            $byType[$type->value] = (int) (clone $query)->where('type', $type->value)->sum('amount');
        }

        $pending = (clone $query)->where('status', AdditionalChargeStatus::PendingApproval->value)->count();
        $approved = (clone $query)->where('status', AdditionalChargeStatus::Approved->value)->count();
        $rejected = (clone $query)->where('status', AdditionalChargeStatus::Rejected->value)->count();
        $paid = (clone $query)->whereIn('status', [AdditionalChargeStatus::Paid->value, AdditionalChargeStatus::OffsetFromDeposit->value])->count();
        $outstanding = (int) (clone $query)->whereIn('status', [
            AdditionalChargeStatus::Approved->value,
            AdditionalChargeStatus::PartiallySettled->value,
        ])->sum('outstanding_amount');

        $table = (clone $query)
            ->with('order')
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->map(fn (AdditionalCharge $charge) => [
                'charge_code' => $charge->charge_code,
                'order_code' => $charge->order?->order_code,
                'type' => $charge->type->value,
                'amount' => (int) $charge->amount,
                'outstanding' => (int) $charge->outstanding_amount,
                'status' => $charge->status->value,
                'payment_status' => $charge->payment_status,
            ])
            ->all();

        return [
            'metrics' => [
                'by_type' => $byType,
                'total' => array_sum($byType),
                'pending_approval' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'paid' => $paid,
                'outstanding' => $outstanding,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,mixed>,table:array<int,array<string,mixed>>}
     */
    public function inventory(array $f): array
    {
        $saleStock = (int) DB::table('product_variants')->sum('stock_quantity');
        $reserved = (int) SalesStockReservation::query()
            ->where('status', SalesReservationStatus::Reserved->value)
            ->sum('quantity');
        $soldQuantity = (int) StockMovement::query()
            ->where('type', StockMovementType::Sale->value)
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->selectRaw('COALESCE(SUM(quantity_before - quantity_after), 0) as sold')
            ->value('sold');

        $assetsByStatus = [];
        foreach (RentalAssetStatus::cases() as $status) {
            $assetsByStatus[$status->value] = RentalAsset::query()->where('status', $status->value)->count();
        }

        $movements = StockMovement::query()
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->all();

        $damage = (int) AdditionalCharge::query()
            ->where('type', AdditionalChargeType::Damage->value)
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->count();
        $lost = (int) AdditionalCharge::query()
            ->where('type', AdditionalChargeType::Lost->value)
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->count();

        $table = ProductVariantQuery::rows();

        return [
            'metrics' => [
                'sale_stock' => $saleStock,
                'reserved_sale_stock' => $reserved,
                'available_sale_stock' => max(0, $saleStock - $reserved),
                'sold_quantity' => $soldQuantity,
                'assets_by_status' => $assetsByStatus,
                'stock_movements' => $movements,
                'damage_incidents' => $damage,
                'lost_incidents' => $lost,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array{metrics:array<string,mixed>,table:array<int,array<string,mixed>>}
     */
    public function rentalOperations(array $f): array
    {
        $orders = RentalOrder::query()->whereBetween('created_at', [$f['start'], $f['end']]);

        $byStatus = [];
        foreach (RentalOrderStatus::cases() as $status) {
            $byStatus[$status->value] = (clone $orders)->where('order_status', $status->value)->count();
        }

        $averageDuration = (float) (clone $orders)->avg('duration_days');
        $overdue = RentalOrder::query()
            ->whereIn('order_status', [
                RentalOrderStatus::Confirmed->value,
                RentalOrderStatus::ReadyForPickup->value,
                RentalOrderStatus::PickedUp->value,
            ])
            ->whereDate('end_date', '<', now(config('app.timezone'))->toDateString())
            ->count();

        $inspections = Inspection::query()->whereBetween('created_at', [$f['start'], $f['end']])->count();
        $lostAssets = RentalAsset::query()->where('status', RentalAssetStatus::Lost->value)->count();
        $damageIncidents = (int) AdditionalCharge::query()
            ->where('type', AdditionalChargeType::Damage->value)
            ->whereBetween('created_at', [$f['start'], $f['end']])
            ->count();

        $table = (clone $orders)
            ->with('customer')
            ->latest('created_at')
            ->limit(500)
            ->get()
            ->map(fn (RentalOrder $order) => [
                'order_code' => $order->order_code,
                'customer' => $order->customer?->name,
                'start_date' => $order->start_date?->toDateString(),
                'end_date' => $order->end_date?->toDateString(),
                'duration_days' => (int) $order->duration_days,
                'status' => $order->order_status->value,
            ])
            ->all();

        return [
            'metrics' => [
                'total_orders' => array_sum($byStatus),
                'by_status' => $byStatus,
                'active_rental' => $byStatus[RentalOrderStatus::PickedUp->value] ?? 0,
                'completed' => $byStatus[RentalOrderStatus::Completed->value] ?? 0,
                'cancelled' => $byStatus[RentalOrderStatus::Cancelled->value] ?? 0,
                'overdue' => $overdue,
                'average_duration_days' => round($averageDuration, 1),
                'inspections' => $inspections,
                'damage_incidents' => $damageIncidents,
                'lost_assets' => $lostAssets,
            ],
            'table' => $table,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return array<string, mixed>
     */
    public function report(string $key, array $f): array
    {
        return match ($key) {
            'rental' => $this->rentalRevenue($f),
            'sales' => $this->retailSales($f),
            'payments' => $this->payments($f),
            'deposits' => $this->deposits($f),
            'charges' => $this->additionalCharges($f),
            'inventory' => $this->inventory($f),
            'operations' => $this->rentalOperations($f),
            default => ['metrics' => $this->dashboard($f), 'table' => []],
        };
    }

    /**
     * Baris CSV untuk laporan tertentu (filter sama dengan layar).
     *
     * @param  array<string, mixed>  $f
     * @return array<int, string>
     */
    public function csvHeaders(string $key): array
    {
        return match ($key) {
            'rental' => ['Kode Order', 'Pelanggan', 'Mulai', 'Selesai', 'Durasi (hari)', 'Status', 'Subtotal', 'Deposit', 'Total', 'Sewa Terbayar'],
            'sales' => ['Kode Order', 'Pelanggan', 'Dikonfirmasi', 'Status', 'Subtotal', 'Diskon', 'Total', 'Jumlah Item'],
            'payments' => ['Tipe', 'Referensi', 'Pelanggan', 'Metode', 'Status', 'Nominal', 'Dibuat', 'Settled'],
            'deposits' => ['Kode Order', 'Pelanggan', 'Wajib', 'Terkumpul', 'Dipotong', 'Direfund', 'Hangus', 'Saldo', 'Status'],
            'charges' => ['Kode Biaya', 'Kode Order', 'Tipe', 'Nominal', 'Outstanding', 'Status', 'Status Bayar'],
            'inventory' => ['Produk', 'SKU', 'Stok', 'Reservasi', 'Tersedia'],
            'operations' => ['Kode Order', 'Pelanggan', 'Mulai', 'Selesai', 'Durasi (hari)', 'Status'],
            default => ['Metrik', 'Nilai'],
        };
    }

    /**
     * @param  array<string, mixed>  $f
     * @return \Generator<int, array<int, mixed>>
     */
    public function csvRows(string $key, array $f): \Generator
    {
        if ($key === 'summary') {
            $summary = $this->dashboard($f);
            yield ['Rental net revenue', $summary['rental']['net_rental_revenue']];
            yield ['Retail net sales', $summary['sales']['net_sales']];
            yield ['Payment collected', $summary['payments']['collected']];
            yield ['Deposit held balance', $summary['deposits']['held_balance']];
            yield ['Additional charges outstanding', $summary['charges']['outstanding']];
            yield ['Overdue rentals', $summary['operations']['overdue']];

            return;
        }

        $report = $this->report($key, $f);

        foreach ($report['table'] as $row) {
            yield array_values($row);
        }
    }
}
