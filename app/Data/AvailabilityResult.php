<?php

namespace App\Data;

use App\Models\RentalAsset;
use App\Support\RentalPeriod;
use Illuminate\Support\Collection;

/**
 * Hasil pemeriksaan ketersediaan untuk satu varian pada satu periode.
 */
final class AvailabilityResult
{
    /**
     * @param  Collection<int, RentalAsset>  $availableAssets  Kandidat unit yang bisa dialokasikan.
     * @param  int  $rentableTotal  Total unit yang layak disewa pada varian ini.
     * @param  int  $blockedTotal  Unit yang sedang terblokir pada periode beririsan.
     */
    public function __construct(
        public readonly int $variantId,
        public readonly RentalPeriod $period,
        public readonly int $requestedQuantity,
        public readonly int $rentableTotal,
        public readonly int $blockedTotal,
        public readonly Collection $availableAssets,
    ) {}

    public function availableQuantity(): int
    {
        return max(0, $this->rentableTotal - $this->blockedTotal);
    }

    public function isAvailable(): bool
    {
        return $this->requestedQuantity >= 1 && $this->availableQuantity() >= $this->requestedQuantity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'variant_id' => $this->variantId,
            'start_date' => $this->period->startDate(),
            'end_date' => $this->period->endDate(),
            'blocking_end_date' => $this->period->blockingEndDate(),
            'duration_days' => $this->period->days(),
            'requested_quantity' => $this->requestedQuantity,
            'rentable_total' => $this->rentableTotal,
            'blocked_total' => $this->blockedTotal,
            'available_quantity' => $this->availableQuantity(),
            'is_available' => $this->isAvailable(),
        ];
    }
}
