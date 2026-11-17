<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\AvailabilityCheckRequest;
use App\Models\ProductVariant;
use App\Services\RentalAvailabilityService;
use App\Services\RentalPricingService;
use App\Support\RentalPeriod;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Pemeriksaan ketersediaan bersifat estimasi saat halaman dibuka.
 * Validasi final tetap dilakukan di dalam transaction ketika order dibuat.
 */
class AvailabilityController extends Controller
{
    public function __construct(
        private readonly RentalAvailabilityService $availability,
        private readonly RentalPricingService $pricing,
    ) {}

    public function check(AvailabilityCheckRequest $request): JsonResponse
    {
        $data = $request->validated();

        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail($data['variant_id']);

        try {
            $period = RentalPeriod::from($data['start_date'], $data['end_date']);
            $period->assertValid(config('gearora.rental'));

            $result = $this->availability->check($variant, (int) $data['quantity'], $period);
            $quote = $this->pricing->quote(
                [['variant' => $variant, 'quantity' => (int) $data['quantity']]],
                $period,
            );
        } catch (Throwable $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'availability' => $result->toArray(),
            'pricing' => $quote->toArray(),
            'formatted' => [
                'subtotal' => rupiah($quote->subtotal),
                'deposit_total' => rupiah($quote->depositTotal),
                'total' => rupiah($quote->total),
                'unit_price' => rupiah($variant->effectiveRentalPrice() ?? 0),
            ],
        ]);
    }
}
