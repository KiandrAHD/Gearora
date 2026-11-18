<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RentalOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\RentalAvailabilityException;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalAssetAssignment;
use App\Models\RentalOrder;
use App\Models\User;
use App\Support\RentalPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orkestrasi pembuatan rental order dan transisi status.
 *
 * Anti-overbooking: validasi ketersediaan diulang di dalam database transaction,
 * dengan row locking pada varian dan unit aset, dan kandidat aset diurutkan
 * secara deterministik untuk mengurangi risiko deadlock.
 */
class RentalOrderService
{
    public function __construct(
        private readonly RentalAvailabilityService $availability,
        private readonly RentalPricingService $pricing,
        private readonly DepositService $deposits,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<int, array{variant_id:int, quantity:int}>  $requestedItems
     */
    public function create(
        User $customer,
        array $requestedItems,
        RentalPeriod $period,
        PaymentMethod $method = PaymentMethod::Transfer,
        ?string $notes = null,
    ): RentalOrder {
        $period->assertValid($this->policy());

        $items = $this->normalizeItems($requestedItems);

        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return DB::transaction(function () use ($customer, $items, $period, $method, $notes) {
                    $variants = ProductVariant::query()
                        ->with('product')
                        ->whereIn('id', array_column($items, 'variant_id'))
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    $pricingLines = [];
                    $allocations = [];

                    foreach ($items as $item) {
                        $variant = $variants->get($item['variant_id']);

                        if ($variant === null || $variant->product === null) {
                            throw new RentalAvailabilityException(
                                'Produk atau varian tidak ditemukan.',
                                requested: $item['quantity'],
                            );
                        }

                        if ($variant->product->status !== ProductStatus::Active) {
                            throw new RentalAvailabilityException(
                                "Produk {$variant->product->name} sedang tidak tersedia untuk dipesan.",
                                variantName: $variant->name,
                                requested: $item['quantity'],
                            );
                        }

                        if (! $variant->product->supportsRental()) {
                            throw new RentalAvailabilityException(
                                "Produk {$variant->product->name} tidak dapat disewa.",
                                variantName: $variant->name,
                                requested: $item['quantity'],
                            );
                        }

                        $assets = $this->availability->ensureAvailable(
                            $variant,
                            $item['quantity'],
                            $period,
                            lock: true,
                        );

                        $allocations[$variant->getKey()] = $assets;
                        $pricingLines[] = ['variant' => $variant, 'quantity' => $item['quantity']];
                    }

                    $quote = $this->pricing->quote($pricingLines, $period);

                    $order = RentalOrder::query()->create([
                        'order_code' => $this->generateOrderCode(),
                        'customer_id' => $customer->getKey(),
                        'start_date' => $period->startDate(),
                        'end_date' => $period->endDate(),
                        'blocking_end_date' => $period->blockingEndDate(),
                        'duration_days' => $quote->durationDays(),
                        'subtotal' => $quote->subtotal,
                        'deposit_total' => $quote->depositTotal,
                        'total' => $quote->total,
                        'order_status' => RentalOrderStatus::PendingPayment,
                        'notes' => $notes,
                    ]);

                    foreach ($quote->lines as $row) {
                        $variantId = $row['variant_id'];

                        $orderItem = $order->items()->create([
                            'product_id' => $row['product_id'],
                            'product_variant_id' => $variantId,
                            'product_name_snapshot' => $row['product_name'],
                            'variant_name_snapshot' => $row['variant_name'],
                            'rental_price_snapshot' => $row['unit_price'],
                            'quantity' => $row['quantity'],
                            'duration_days' => $row['duration_days'],
                            'line_total' => $row['line_total'],
                        ]);

                        /** @var RentalAsset $asset */
                        foreach ($allocations[$variantId] as $asset) {
                            RentalAssetAssignment::query()->create([
                                'rental_order_id' => $order->getKey(),
                                'rental_order_item_id' => $orderItem->getKey(),
                                'rental_asset_id' => $asset->getKey(),
                                'assigned_at' => now(),
                            ]);
                        }
                    }

                    $order->payments()->create([
                        'method' => $method,
                        'amount' => $quote->total,
                        'rental_amount' => (int) round((float) $quote->subtotal),
                        'deposit_amount' => (int) round((float) $quote->depositTotal),
                        'status' => PaymentStatus::Unpaid,
                    ]);

                    $this->deposits->ensureForOrder($order);

                    $this->logger->log(
                        'rental_order.created',
                        $order,
                        "Order rental {$order->order_code} dibuat.",
                        [
                            'order_code' => $order->order_code,
                            'start_date' => $period->startDate(),
                            'end_date' => $period->endDate(),
                            'duration_days' => $quote->durationDays(),
                            'total' => $quote->total,
                            'method' => $method->value,
                        ],
                        $customer,
                    );

                    return $order->load(['items', 'payments', 'assignments']);
                });
            } catch (QueryException $exception) {
                if ($attempts < 3 && $this->isDuplicateOrderCode($exception)) {
                    continue;
                }

                throw $exception;
            }
        }
    }

    /**
     * Transisi status order dengan validasi transisi dan precondition (server-side).
     *
     * `$viaWorkflow` di-set true hanya oleh workflow service (handover/return) yang
     * sudah menulis bukti fisik (handover record, return record, inspeksi) sebelum
     * memindahkan status. Dropdown admin generik tidak boleh mencapai status
     * picked_up/returned/completed.
     */
    public function transition(
        RentalOrder $order,
        RentalOrderStatus $target,
        ?User $actor = null,
        ?string $reason = null,
        bool $viaWorkflow = false,
    ): RentalOrder {
        $current = $order->order_status;

        if ($current === $target) {
            return $order;
        }

        if (! $current->canTransitionTo($target)) {
            throw new InvalidOrderTransitionException(
                "Transisi status {$current->value} ke {$target->value} tidak diizinkan."
            );
        }

        if ($target->isWorkflowOnlyTarget() && ! $viaWorkflow) {
            throw new InvalidOrderTransitionException(
                "Status {$target->value} hanya dapat dicapai melalui alur operasional (serah terima/pengembalian/inspeksi)."
            );
        }

        $this->assertPreconditions($order, $target);

        return DB::transaction(function () use ($order, $current, $target, $actor, $reason) {
            $order = RentalOrder::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            // Validasi ulang setelah lock: status bisa berubah oleh request paralel.
            if ($order->order_status !== $current) {
                throw new InvalidOrderTransitionException(
                    "Status order sudah berubah menjadi {$order->order_status->value}; ulangi aksi."
                );
            }

            $attributes = ['order_status' => $target];

            if ($target === RentalOrderStatus::Cancelled) {
                $attributes['cancelled_at'] = now();
                $attributes['cancellation_reason'] = $reason;
            }

            $order->fill($attributes)->save();

            // Pelepasan unit HANYA saat order dibatalkan. Untuk return/completed,
            // pelepasan dilakukan per unit setelah inspeksi (lihat ADR-5.2).
            if ($target === RentalOrderStatus::Cancelled) {
                $this->releaseAssignments($order);
            }

            $this->logger->log(
                'rental_order.status_changed',
                $order,
                "Status order {$order->order_code}: {$current->value} -> {$target->value}.",
                [
                    'from' => $current->value,
                    'to' => $target->value,
                    'reason' => $reason,
                ],
                $actor,
            );

            return $order->refresh();
        });
    }

    /**
     * Precondition transisi status.
     */
    private function assertPreconditions(RentalOrder $order, RentalOrderStatus $target): void
    {
        switch ($target) {
            case RentalOrderStatus::Confirmed:
                $hasSettledPayment = $order->payments()
                    ->where('status', PaymentStatus::Verified->value)
                    ->exists();

                if (! $hasSettledPayment) {
                    throw new InvalidOrderTransitionException('Order hanya dapat dikonfirmasi setelah pembayaran terverifikasi.');
                }
                break;

            case RentalOrderStatus::PickedUp:
                if (! $order->handovers()->where('status', 'completed')->exists()) {
                    throw new InvalidOrderTransitionException('Status picked_up membutuhkan catatan serah terima.');
                }
                break;

            case RentalOrderStatus::Returned:
                if (! $order->rentalReturns()->where('status', 'completed')->exists()) {
                    throw new InvalidOrderTransitionException('Status returned membutuhkan catatan pengembalian.');
                }
                break;

            case RentalOrderStatus::Completed:
                $unreleased = $order->assignments()->whereNull('released_at')->count();

                if ($unreleased > 0) {
                    throw new InvalidOrderTransitionException('Order belum dapat diselesaikan; masih ada unit yang belum selesai diinspeksi.');
                }

                if ($order->additionalCharges()->whereIn('status', ['pending_approval', 'approved', 'partially_settled'])->exists()) {
                    throw new InvalidOrderTransitionException('Order belum dapat diselesaikan; masih ada biaya tambahan yang belum selesai.');
                }
                break;

            default:
                break;
        }
    }

    /**
     * Lepaskan seluruh assignment order (dipakai saat order dibatalkan).
     */
    public function releaseAssignments(RentalOrder $order): void
    {
        $order->assignments()
            ->whereNull('released_at')
            ->update(['released_at' => now()]);
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
                throw new RentalAvailabilityException('Item pesanan tidak valid.');
            }

            $normalized[$variantId] = ($normalized[$variantId] ?? 0) + $quantity;
        }

        if ($normalized === []) {
            throw new RentalAvailabilityException('Pesanan harus berisi minimal satu produk.');
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
            $code = 'GRR-'.$date.'-'.strtoupper(Str::random(4));

            if (! RentalOrder::query()->where('order_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode order unik. Coba lagi.');
    }

    private function isDuplicateOrderCode(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'order_code');
    }

    /**
     * @return array{min_days:int, max_days:int, horizon_days:int, enforce_horizon:bool}
     */
    private function policy(): array
    {
        return config('gearora.rental');
    }
}
