<?php

namespace App\Services;

use App\Enums\ProductStatus;
use App\Enums\SalesReservationStatus;
use App\Enums\StockMovementType;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesStockReservation;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stok jual retail: reservasi, konsumsi, dan pelepasan.
 *
 * Terpisah dari rental_assets. Satu-satunya penulis `stock_quantity` untuk
 * penjualan adalah service ini, dan setiap perubahan menghasilkan StockMovement.
 * `stock_quantity` (fisik) dikurangi HANYA saat pembayaran terkonfirmasi
 * (consume), bukan saat order dibuat.
 */
class SalesStockService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /** Jumlah stok yang sedang direservasi (belum dikonsumsi) untuk satu varian. */
    public function reservedQuantity(int $variantId): int
    {
        return (int) SalesStockReservation::query()
            ->where('product_variant_id', $variantId)
            ->active()
            ->sum('quantity');
    }

    /** Stok jual tersedia = stok fisik - reservasi aktif. */
    public function availableQuantity(ProductVariant $variant): int
    {
        return max(0, (int) $variant->stock_quantity - $this->reservedQuantity($variant->getKey()));
    }

    /**
     * Validasi & reservasi stok untuk order yang baru dibuat.
     *
     * Harus dipanggil di dalam transaction yang sudah mengunci varian
     * (lockForUpdate) secara deterministik.
     *
     * @param  array<int, array{variant_id:int, quantity:int}>  $items
     */
    public function reserveForOrder(SalesOrder $order, array $items): void
    {
        $expiresAt = now()->addMinutes((int) config('gearora.sales.reservation_minutes', 120));

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', array_column($items, 'variant_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $line) {
            $variant = $variants->get($line['variant_id']);

            if ($variant === null || $variant->product === null) {
                throw new InvalidArgumentException('Produk atau varian tidak ditemukan.');
            }

            if ($variant->product->status !== ProductStatus::Active) {
                throw new InvalidArgumentException("Produk {$variant->product->name} tidak tersedia untuk dibeli.");
            }

            if (! $variant->product->supportsSale()) {
                throw new InvalidArgumentException("Produk {$variant->product->name} tidak dijual (hanya sewa).");
            }

            if (! $variant->is_active) {
                throw new InvalidArgumentException("Varian {$variant->name} sudah tidak aktif.");
            }

            if ($this->effectiveSalePrice($variant) <= 0) {
                throw new InvalidArgumentException("Harga jual varian {$variant->name} belum valid.");
            }

            if ($line['quantity'] > $this->availableQuantity($variant)) {
                throw new InvalidArgumentException(
                    "Stok {$variant->product->name} - {$variant->name} tidak mencukupi. "
                    ."Diminta {$line['quantity']}, tersedia ".$this->availableQuantity($variant).'.'
                );
            }

            $item = $order->items()->where('product_variant_id', $variant->getKey())->firstOrFail();

            SalesStockReservation::query()->create([
                'sales_order_id' => $order->getKey(),
                'sales_order_item_id' => $item->getKey(),
                'product_variant_id' => $variant->getKey(),
                'quantity' => $line['quantity'],
                'status' => SalesReservationStatus::Reserved,
                'reserved_at' => now(),
                'expires_at' => $expiresAt,
            ]);
        }
    }

    /**
     * Konsumsi reservasi: kurangi stok fisik & catat movement. Idempotent.
     * Hanya reservasi berstatus `reserved` yang diproses; pemanggilan ulang
     * (mis. webhook ganda) tidak menurunkan stok dua kali.
     */
    public function consumeForOrder(SalesOrder $order, ?User $actor = null): int
    {
        return DB::transaction(function () use ($order, $actor) {
            $reservations = SalesStockReservation::query()
                ->where('sales_order_id', $order->getKey())
                ->active()
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get();

            $consumed = 0;

            foreach ($reservations as $reservation) {
                $variant = ProductVariant::query()
                    ->whereKey($reservation->product_variant_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $before = (int) $variant->stock_quantity;

                if ($before < (int) $reservation->quantity) {
                    throw new InvalidArgumentException(
                        "Stok {$variant->sku} tidak cukup untuk memenuhi penjualan (tersisa {$before})."
                    );
                }

                $after = $before - (int) $reservation->quantity;

                $variant->forceFill(['stock_quantity' => $after])->save();

                StockMovement::query()->create([
                    'product_variant_id' => $variant->getKey(),
                    'type' => StockMovementType::Sale,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reason' => 'Penjualan retail '.$order->order_code,
                    'user_id' => $actor?->getKey(),
                    'created_at' => now(),
                ]);

                $reservation->forceFill([
                    'status' => SalesReservationStatus::Consumed,
                    'consumed_at' => now(),
                ])->save();

                $consumed++;
            }

            if ($consumed > 0) {
                $this->logger->log(
                    'sales.stock_consumed',
                    $order,
                    "Stok jual dikurangi untuk order {$order->order_code}.",
                    ['order_code' => $order->order_code, 'reservations' => $consumed],
                    $actor,
                );
            }

            return $consumed;
        });
    }

    /**
     * Lepaskan seluruh reservasi aktif order (pembatalan/kedaluwarsa).
     * Stok fisik TIDAK dikurangi. Idempotent.
     */
    public function releaseForOrder(SalesOrder $order, SalesReservationStatus $status, ?User $actor = null): int
    {
        if (! in_array($status, [SalesReservationStatus::Released, SalesReservationStatus::Expired], true)) {
            throw new InvalidArgumentException('Pelepasan reservasi hanya untuk status released/expired.');
        }

        return DB::transaction(function () use ($order, $status, $actor) {
            $affected = SalesStockReservation::query()
                ->where('sales_order_id', $order->getKey())
                ->where('status', SalesReservationStatus::Reserved->value)
                ->update([
                    'status' => $status->value,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected > 0) {
                $this->logger->log(
                    'sales.stock_released',
                    $order,
                    "Reservasi stok order {$order->order_code} dilepas ({$status->value}).",
                    ['order_code' => $order->order_code, 'reservations' => $affected],
                    $actor,
                );
            }

            return $affected;
        });
    }

    /** @return Collection<int, SalesStockReservation> */
    public function activeReservationsForOrder(SalesOrder $order): Collection
    {
        return SalesStockReservation::query()
            ->where('sales_order_id', $order->getKey())
            ->active()
            ->get();
    }

    public function effectiveSalePrice(ProductVariant $variant): int
    {
        return Money::toInt($variant->effectiveSalePrice());
    }
}
