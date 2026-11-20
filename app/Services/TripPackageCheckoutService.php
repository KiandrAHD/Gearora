<?php

namespace App\Services;

use App\Data\TripPackageData;
use App\Enums\ProductStatus;
use App\Enums\TripPackageStatus;
use App\Models\ProductVariant;
use App\Models\TripPackage;
use App\Models\User;
use App\Support\Money;
use App\Support\RentalPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orkestrasi Paket Trip Planner: satu paket dapat memuat item SEWA dan item BELI
 * sekaligus (mixed). Harga dan ketersediaan TIDAK dipercaya dari klien; seluruh
 * item divalidasi ulang lewat layanan domain yang sudah ada:
 *
 * - RentalOrderService  → alokasi unit + harga sewa (RentalAvailabilityService).
 * - SalesOrderService   → reservasi stok + harga jual (SalesStockService).
 *
 * Keputusan arsitektur (lihat ADR_PHASE_9_TRIP_PACKAGE.md): paket membuat dua
 * order (rental & sales) yang tertaut; pembayaran tetap mengikuti alur per-order
 * (Midtrans/transfer) masing-masing. Paket hanya menyimpan snapshot & agregasi
 * status, bukan nominal atau status pembayaran.
 */
class TripPackageCheckoutService
{
    public function __construct(
        private readonly RentalOrderService $rentalOrders,
        private readonly SalesOrderService $salesOrders,
    ) {}

    public function checkout(User $customer, TripPackageData $data): TripPackage
    {
        // Satu checkout draft menghasilkan satu paket. Kunci idempotensi hidup
        // di session/controller; service tetap mencegah duplikasi dalam transaksi
        // bila caller mengirim ulang data yang sama dengan checkout_key.
        $checkoutKey = $data->checkoutKey ?? null;

        if ($checkoutKey !== null) {
            $existing = TripPackage::query()
                ->where('customer_id', $customer->getKey())
                ->where('checkout_key', $checkoutKey)
                ->first();

            if ($existing !== null) {
                return $existing->load(['rentalOrder', 'salesOrder']);
            }
        }

        $items = $this->normalizeItems($data->items);

        if ($items === []) {
            throw new RuntimeException('Paket tidak memiliki gear yang dipilih.');
        }

        $rentalItems = array_values(array_filter($items, fn (array $i) => $i['fulfillment'] === 'sewa'));
        $saleItems = array_values(array_filter($items, fn (array $i) => $i['fulfillment'] === 'beli'));

        $period = null;

        if ($rentalItems !== []) {
            if (! filled($data->plan->startDate) || ! filled($data->plan->endDate)) {
                throw new RuntimeException('Tanggal sewa wajib diisi untuk gear rental.');
            }

            $period = RentalPeriod::from($data->plan->startDate, $data->plan->endDate);
            $period->assertValid(config('gearora.rental'));
        }

        $method = $data->method;

        $execute = fn (): TripPackage => DB::transaction(function () use ($customer, $data, $checkoutKey, $items, $rentalItems, $saleItems, $period, $method) {
            $rentalOrder = $rentalItems === []
                ? null
                : $this->rentalOrders->create($customer, $rentalItems, $period, $method, $this->notes($data, 'rental'));

            $salesOrder = $saleItems === []
                ? null
                : $this->salesOrders->checkout($customer, $saleItems, $method, $this->notes($data, 'beli'));

            $package = TripPackage::query()->create([
                'code' => $this->generateCode(),
                'checkout_key' => $checkoutKey ?? (string) Str::uuid(),
                'customer_id' => $customer->getKey(),
                'activity' => $data->plan->activity,
                'destination' => $data->plan->destination,
                'location' => $data->plan->location,
                'experience_level' => $data->plan->experience->value,
                'duration_days' => $data->plan->durationDays,
                'participants' => $data->plan->participants,
                'mode' => $data->plan->mode->value,
                'start_date' => $period?->startDate(),
                'end_date' => $period?->endDate(),
                'status' => TripPackageStatus::AwaitingPayment->value,
                'rental_order_id' => $rentalOrder?->getKey(),
                'sales_order_id' => $salesOrder?->getKey(),
                'plan_snapshot' => $this->snapshot($data, $items, $rentalOrder, $salesOrder),
            ]);

            return $package->load(['rentalOrder', 'salesOrder']);
        });

        if ($checkoutKey === null) {
            return $execute();
        }

        // Serialisasi submit ganda untuk kunci yang sama. Bila paket sudah
        // terbentuk (request kedua setelah request pertama selesai), kembalikan
        // paket yang sama alih-alih membuat order child baru.
        return Cache::lock('trip-checkout:'.$customer->getKey().':'.$checkoutKey, 15)
            ->block(10, function () use ($customer, $checkoutKey, $execute): TripPackage {
                $existing = TripPackage::query()
                    ->where('customer_id', $customer->getKey())
                    ->where('checkout_key', $checkoutKey)
                    ->first();

                if ($existing !== null) {
                    return $existing->load(['rentalOrder', 'salesOrder']);
                }

                return $execute();
            });
    }

    /**
     * @param  array<int, array{variant_id:int, quantity:int, fulfillment:string}>  $items
     * @return array<int, array{variant_id:int, quantity:int, fulfillment:string}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $variantId = (int) ($item['variant_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $fulfillment = (string) ($item['fulfillment'] ?? '');

            if ($variantId <= 0 || $quantity <= 0 || ! in_array($fulfillment, ['sewa', 'beli'], true)) {
                throw new RuntimeException('Item paket tidak valid.');
            }

            if ($quantity > (int) config('gearora.sales.max_quantity_per_line', 99)) {
                throw new RuntimeException('Jumlah item melebihi batas yang diizinkan.');
            }

            $variant = ProductVariant::query()->with('product')->find($variantId);

            if ($variant === null || $variant->product === null || $variant->product->status !== ProductStatus::Active) {
                throw new RuntimeException('Produk atau varian tidak ditemukan atau tidak aktif.');
            }

            if (! $variant->is_active) {
                throw new RuntimeException("Varian {$variant->name} sudah tidak aktif.");
            }

            $price = $fulfillment === 'sewa'
                ? Money::toInt($variant->effectiveRentalPrice())
                : Money::toInt($variant->effectiveSalePrice());

            if ($fulfillment === 'sewa' && ! $variant->product->supportsRental()) {
                throw new RuntimeException("Produk {$variant->product->name} tidak dapat disewa.");
            }

            if ($fulfillment === 'beli' && ! $variant->product->supportsSale()) {
                throw new RuntimeException("Produk {$variant->product->name} tidak dijual.");
            }

            if ($price <= 0) {
                throw new RuntimeException("Harga {$fulfillment} varian {$variant->name} belum valid.");
            }

            $key = $fulfillment.':'.$variantId;
            $normalized[$key] = [
                'variant_id' => $variantId,
                'quantity' => ($normalized[$key]['quantity'] ?? 0) + $quantity,
                'fulfillment' => $fulfillment,
            ];
        }

        ksort($normalized);

        return array_values($normalized);
    }

    private function notes(TripPackageData $data, string $kind): string
    {
        $base = "Paket trip {$data->plan->destination} ({$kind}).";

        return $data->notes !== null && $data->notes !== '' ? $base.' '.$data->notes : $base;
    }

    /**
     * @param  array<int, array{variant_id:int, quantity:int, fulfillment:string}>  $items
     * @return array<string, mixed>
     */
    private function snapshot(TripPackageData $data, array $items, ?object $rentalOrder, ?object $salesOrder): array
    {
        return [
            'destination' => $data->plan->destination,
            'activity' => $data->plan->activity,
            'experience' => $data->plan->experience->value,
            'items' => $items,
            'generated_at' => now()->toIso8601String(),
            'rental_total' => $rentalOrder !== null ? (int) $rentalOrder->total : 0,
            'sales_total' => $salesOrder !== null ? (int) $salesOrder->total : 0,
        ];
    }

    private function generateCode(): string
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $code = 'GRP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

            if (! TripPackage::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode paket unik. Coba lagi.');
    }
}
