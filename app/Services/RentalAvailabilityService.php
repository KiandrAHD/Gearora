<?php

namespace App\Services;

use App\Data\AvailabilityResult;
use App\Enums\RentalOrderStatus;
use App\Exceptions\RentalAvailabilityException;
use App\Models\ProductVariant;
use App\Models\RentalAsset;
use App\Models\RentalAssetAssignment;
use App\Support\RentalPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sumber kebenaran ketersediaan rental per unit fisik.
 *
 * Ketersediaan dihitung dari unit layak sewa dikurangi unit yang teralokasi pada
 * order yang statusnya masih memblokir stok dan periodenya beririsan.
 */
class RentalAvailabilityService
{
    /**
     * @return Collection<int, RentalAsset>
     */
    public function rentableAssets(int $variantId, bool $lock = false): Collection
    {
        return RentalAsset::query()
            ->where('product_variant_id', $variantId)
            ->rentable()
            ->orderBy('asset_code')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get();
    }

    /**
     * ID unit yang terblokir karena alokasi pada order aktif yang periodenya beririsan.
     *
     * @return array<int, int>
     */
    public function blockedAssetIds(int $variantId, RentalPeriod $period): array
    {
        return RentalAssetAssignment::query()
            ->whereNull('released_at')
            ->whereHas('asset', fn ($query) => $query->where('product_variant_id', $variantId))
            ->whereHas('order', fn ($query) => $query
                ->blocking()
                ->overlapping($period->startDate(), $period->blockingEndDate()))
            ->pluck('rental_asset_id')
            ->unique()
            ->values()
            ->all();
    }

    public function check(
        ProductVariant $variant,
        int $quantity,
        RentalPeriod $period,
        bool $lock = false,
    ): AvailabilityResult {
        $assets = $this->rentableAssets($variant->getKey(), $lock);
        $blocked = $this->blockedAssetIds($variant->getKey(), $period);

        $available = $assets
            ->reject(fn (RentalAsset $asset) => in_array($asset->getKey(), $blocked, true))
            ->values();

        return new AvailabilityResult(
            variantId: $variant->getKey(),
            period: $period,
            requestedQuantity: max(0, $quantity),
            rentableTotal: $assets->count(),
            blockedTotal: count($blocked),
            availableAssets: $available,
        );
    }

    /**
     * ID varian yang memiliki minimal satu unit bebas pada periode tertentu.
     * Dipakai filter ketersediaan di katalog publik.
     *
     * @param  array<int, int>|null  $variantIds
     * @return array<int, int>
     */
    public function availableVariantIds(RentalPeriod $period, ?array $variantIds = null): array
    {
        $variants = ProductVariant::query()
            ->active()
            ->when($variantIds !== null, fn ($query) => $query->whereIn('id', $variantIds))
            ->withCount(['rentalAssets as rentable_count' => fn ($query) => $query->rentable()])
            ->get(['id']);

        if ($variants->isEmpty()) {
            return [];
        }

        $blocked = DB::table('rental_asset_assignments as assignments')
            ->join('rental_assets as assets', 'assets.id', '=', 'assignments.rental_asset_id')
            ->join('rental_orders as orders', 'orders.id', '=', 'assignments.rental_order_id')
            ->whereNull('assignments.released_at')
            ->whereIn('orders.order_status', array_map(
                fn (RentalOrderStatus $status) => $status->value,
                RentalOrderStatus::blockingStatuses()
            ))
            ->where('orders.start_date', '<', $period->blockingEndDate())
            ->where('orders.blocking_end_date', '>', $period->startDate())
            ->groupBy('assets.product_variant_id')
            ->selectRaw('assets.product_variant_id as variant_id, count(distinct assignments.rental_asset_id) as blocked_count')
            ->pluck('blocked_count', 'variant_id');

        return $variants
            ->filter(function (ProductVariant $variant) use ($blocked) {
                $blockedCount = (int) ($blocked[$variant->getKey()] ?? 0);

                return ((int) $variant->rentable_count - $blockedCount) > 0;
            })
            ->pluck('id')
            ->all();
    }

    /**
     * Pastikan kuantitas tersedia; lempar exception jika tidak.
     *
     * @return Collection<int, RentalAsset> Unit yang boleh dialokasikan.
     */
    public function ensureAvailable(
        ProductVariant $variant,
        int $quantity,
        RentalPeriod $period,
        bool $lock = true,
    ): Collection {
        $result = $this->check($variant, $quantity, $period, $lock);

        if (! $result->isAvailable()) {
            $variantLabel = $variant->name;

            throw new RentalAvailabilityException(
                "Ketersediaan {$variantLabel} tidak mencukupi untuk periode yang dipilih. "
                ."Diminta {$result->requestedQuantity}, tersedia {$result->availableQuantity()}.",
                variantName: $variantLabel,
                requested: $result->requestedQuantity,
                available: $result->availableQuantity(),
            );
        }

        return $result->availableAssets->take($quantity)->values();
    }
}
