<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\SalesReservationStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orkestrasi Sales Order retail: pembuatan checkout dan transisi status.
 *
 * Anti-overselling: validasi stok diulang di dalam transaction dengan row lock
 * pada varian, lalu reservasi dibuat. Stok fisik berkurang hanya saat pembayaran
 * terkonfirmasi (SalesStockService::consumeForOrder).
 */
class SalesOrderService
{
    public function __construct(
        private readonly SalesStockService $stock,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, array{variant_id:int, quantity:int}>  $requestedItems
     */
    public function checkout(
        User $customer,
        array $requestedItems,
        PaymentMethod $method = PaymentMethod::Cash,
        ?string $notes = null,
    ): SalesOrder {
        $items = $this->normalizeItems($requestedItems);

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return DB::transaction(function () use ($customer, $items, $method, $notes) {
                    $variants = ProductVariant::query()
                        ->with('product')
                        ->whereIn('id', array_column($items, 'variant_id'))
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $subtotal = 0;
                    $lines = [];

                    foreach ($items as $item) {
                        $variant = $variants->get($item['variant_id']);

                        if ($variant === null || $variant->product === null) {
                            throw new RuntimeException('Produk atau varian tidak ditemukan.');
                        }

                        if ($variant->product->status !== ProductStatus::Active) {
                            throw new RuntimeException("Produk {$variant->product->name} tidak tersedia untuk dibeli.");
                        }

                        if (! $variant->product->supportsSale()) {
                            throw new RuntimeException("Produk {$variant->product->name} tidak dijual (hanya sewa).");
                        }

                        if (! $variant->is_active) {
                            throw new RuntimeException("Varian {$variant->name} sudah tidak aktif.");
                        }

                        $unitPrice = Money::toInt($variant->effectiveSalePrice());

                        if ($unitPrice <= 0) {
                            throw new RuntimeException("Harga jual varian {$variant->name} belum valid.");
                        }

                        $lineTotal = $unitPrice * $item['quantity'];
                        $subtotal += $lineTotal;

                        $lines[] = [
                            'variant' => $variant,
                            'quantity' => $item['quantity'],
                            'unit_price' => $unitPrice,
                            'line_total' => $lineTotal,
                        ];
                    }

                    $order = SalesOrder::query()->create([
                        'order_code' => $this->generateOrderCode(),
                        'customer_id' => $customer->getKey(),
                        'subtotal' => $subtotal,
                        'discount_total' => 0,
                        'total' => $subtotal,
                        'payment_status' => PaymentStatus::Unpaid,
                        'order_status' => SalesOrderStatus::PendingPayment,
                        'notes' => $notes,
                        'expires_at' => now()->addMinutes((int) config('gearora.sales.reservation_minutes', 120)),
                    ]);

                    foreach ($lines as $line) {
                        /** @var ProductVariant $variant */
                        $variant = $line['variant'];

                        $order->items()->create([
                            'product_id' => $variant->product_id,
                            'product_variant_id' => $variant->getKey(),
                            'product_name_snapshot' => $variant->product->name,
                            'variant_name_snapshot' => $variant->name,
                            'sku_snapshot' => $variant->sku,
                            'unit_price_snapshot' => $line['unit_price'],
                            'quantity' => $line['quantity'],
                            'line_total' => $line['line_total'],
                        ]);
                    }

                    // Reservasi stok (lock varian → cek ketersediaan → buat reservasi).
                    $this->stock->reserveForOrder($order, $items);

                    $order->payments()->create([
                        'method' => $method,
                        'amount' => $subtotal,
                        'status' => PaymentStatus::Unpaid,
                    ]);

                    $this->logger->log(
                        'sales_order.created',
                        $order,
                        "Sales order {$order->order_code} dibuat.",
                        [
                            'order_code' => $order->order_code,
                            'total' => $subtotal,
                            'method' => $method->value,
                        ],
                        $customer,
                    );

                    return $order->load(['items', 'payments', 'reservations']);
                });
            } catch (QueryException $exception) {
                if ($attempts < 3 && str_contains($exception->getMessage(), 'order_code')) {
                    continue;
                }

                throw $exception;
            }
        }
    }

    /**
     * Transisi status Sales Order dengan validasi & precondition server-side.
     */
    public function transition(
        SalesOrder $order,
        SalesOrderStatus $target,
        ?User $actor = null,
        ?string $reason = null,
    ): SalesOrder {
        $current = $order->order_status;

        if ($current === $target) {
            return $order;
        }

        if (! $current->canTransitionTo($target)) {
            throw new InvalidOrderTransitionException(
                "Transisi status {$current->value} ke {$target->value} tidak diizinkan."
            );
        }

        $this->assertPreconditions($order, $target);

        return DB::transaction(function () use ($order, $current, $target, $actor, $reason) {
            $order = SalesOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($order->order_status !== $current) {
                throw new InvalidOrderTransitionException(
                    "Status order sudah berubah menjadi {$order->order_status->value}; ulangi aksi."
                );
            }

            $attributes = ['order_status' => $target];

            if ($target === SalesOrderStatus::Confirmed) {
                $attributes['confirmed_at'] = now();
            }

            if ($target === SalesOrderStatus::ReadyForPickup) {
                $attributes['pickup_ready_at'] = now();
            }

            if ($target === SalesOrderStatus::Completed) {
                $attributes['completed_at'] = now();
            }

            if ($target === SalesOrderStatus::Cancelled) {
                $attributes['cancelled_at'] = now();
                $attributes['cancellation_reason'] = $reason;
                $attributes['cancelled_by'] = $actor?->getKey();
            }

            $order->forceFill($attributes)->save();

            if (in_array($target, [SalesOrderStatus::Cancelled, SalesOrderStatus::Expired], true)) {
                $this->stock->releaseForOrder(
                    $order,
                    $target === SalesOrderStatus::Expired ? SalesReservationStatus::Expired : SalesReservationStatus::Released,
                    $actor,
                );
            }

            $this->logger->log(
                'sales_order.status_changed',
                $order,
                "Status sales order {$order->order_code}: {$current->value} -> {$target->value}.",
                ['from' => $current->value, 'to' => $target->value, 'reason' => $reason],
                $actor,
            );

            return $order->refresh();
        });
    }

    public function cancel(SalesOrder $order, User $actor, string $reason): SalesOrder
    {
        if ($order->order_status === SalesOrderStatus::Completed) {
            throw new InvalidOrderTransitionException('Order yang sudah selesai tidak dapat dibatalkan.');
        }

        return $this->transition($order, SalesOrderStatus::Cancelled, $actor, $reason);
    }

    private function assertPreconditions(SalesOrder $order, SalesOrderStatus $target): void
    {
        if ($target === SalesOrderStatus::Confirmed && ! $order->hasVerifiedPayment()) {
            throw new InvalidOrderTransitionException('Order hanya dapat dikonfirmasi setelah pembayaran terverifikasi.');
        }
    }

    /**
     * @param  array<int, array{variant_id:int, quantity:int}>  $items
     * @return array<int, array{variant_id:int, quantity:int}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($variantId <= 0 || $quantity <= 0) {
                throw new RuntimeException('Item pesanan tidak valid.');
            }

            $normalized[$variantId] = ($normalized[$variantId] ?? 0) + $quantity;
        }

        if ($normalized === []) {
            throw new RuntimeException('Keranjang kosong.');
        }

        ksort($normalized);

        return array_map(
            fn (int $variantId, int $quantity) => ['variant_id' => $variantId, 'quantity' => $quantity],
            array_keys($normalized),
            array_values($normalized),
        );
    }

    private function generateOrderCode(): string
    {
        $date = CarbonImmutable::now(config('app.timezone'))->format('Ymd');

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $code = 'GRS-'.$date.'-'.strtoupper(Str::random(4));

            if (! SalesOrder::query()->where('order_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode sales order unik. Coba lagi.');
    }
}
