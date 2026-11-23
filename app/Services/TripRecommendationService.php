<?php

namespace App\Services;

use App\Data\TripPlanData;
use App\Enums\ProductStatus;
use App\Enums\TripActivity;
use App\Enums\TripServiceMode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TripRecommendationRule;
use App\Support\Money;
use App\Support\RentalPeriod;
use Illuminate\Support\Collection;

/**
 * Rule engine Trip Planner deterministic dan data-driven.
 *
 * Engine hanya membaca rule aktif dan katalog aktif. Tidak ada random, LLM,
 * executable rule, klaim availability, atau data kondisi tujuan yang ditebak.
 */
class TripRecommendationService
{
    public function __construct(private readonly RentalAvailabilityService $availability) {}

    /**
     * @return array<int, array{
     *   rule:TripRecommendationRule,
     *   product:Product,
     *   variant:ProductVariant,
     *   quantity:int,
     *   reason:string,
     *   mode:string,
     *   availability_checked:bool,
     *   available:bool|null
     * }>
     */
    public function recommend(TripPlanData $plan): array
    {
        $rules = TripRecommendationRule::query()
            ->active()
            ->forActivity($plan->activity)
            ->with([
                'category',
                'product' => fn ($query) => $query->with(['category', 'variants', 'primaryImage']),
                'variant.product',
            ])
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $results = [];
        $seenVariants = [];

        foreach ($rules as $rule) {
            if (! $this->matches($rule, $plan)) {
                continue;
            }

            foreach ($this->candidates($rule, $plan->mode) as $candidate) {
                [$product, $variant] = $candidate;
                $variantId = $variant->getKey();

                // Satu varian hanya tampil satu kali. Rule priority + id stabil.
                if (isset($seenVariants[$variantId])) {
                    continue;
                }

                if (! $this->eligible($product, $variant, $plan->mode)) {
                    continue;
                }

                $available = null;
                $availabilityChecked = false;

                // Tanggal bersifat opsional. Bila tidak ada, tidak klaim tersedia.
                // Ketersediaan rental hanya relevan untuk produk yang memang dapat
                // disewa (mode fleksibel tetap memuat item beli yang tidak terikat tanggal).
                if ($plan->mode->supportsRental() && $product->supportsRental() && filled($plan->startDate) && filled($plan->endDate)) {
                    try {
                        $period = RentalPeriod::from($plan->startDate, $plan->endDate);
                        $period->assertValid(config('gearora.rental'));
                        $availabilityChecked = true;
                        $available = $this->availability
                            ->check($variant, $this->quantity($rule, $plan), $period)
                            ->isAvailable();

                        if (! $available && $plan->mode === TripServiceMode::Sewa) {
                            continue;
                        }
                    } catch (\Throwable) {
                        // Input tanggal divalidasi Form Request; bila service
                        // menolak, tetap tampilkan gear tanpa klaim availability.
                        $availabilityChecked = false;
                        $available = null;
                    }
                }

                $quantity = $this->quantity($rule, $plan);
                $results[] = [
                    'rule' => $rule,
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'reason' => $this->reason($rule, $plan),
                    'mode' => $plan->mode->value,
                    'supports_rental' => $product->supportsRental() && Money::toInt($variant->effectiveRentalPrice()) > 0,
                    'supports_sale' => $product->supportsSale() && $this->salePrice($variant) > 0,
                    'rental_price' => Money::toInt($variant->effectiveRentalPrice()),
                    'sale_price' => $this->salePrice($variant),
                    'availability_checked' => $availabilityChecked,
                    'available' => $available,
                ];
                $seenVariants[$variantId] = true;

                if (count($results) >= (int) config('gearora.trip.max_results', 12)) {
                    return $results;
                }
            }
        }

        return $results;
    }

    private function matches(TripRecommendationRule $rule, TripPlanData $plan): bool
    {
        $duration = $plan->durationDays;
        $participants = $plan->participants;

        if ($rule->activity !== TripActivity::ANY && $rule->activity !== $plan->activity) {
            return false;
        }

        if (! $rule->service_mode->matches($plan->mode)) {
            return false;
        }

        if ($duration < (int) $rule->min_duration_days
            || ($rule->max_duration_days !== null && $duration > (int) $rule->max_duration_days)) {
            return false;
        }

        if ($participants < (int) $rule->min_participants
            || ($rule->max_participants !== null && $participants > (int) $rule->max_participants)) {
            return false;
        }

        $experienceLevels = $rule->requiredExperienceLevels();

        if ($experienceLevels !== [] && ! in_array($plan->experience->value, $experienceLevels, true)) {
            return false;
        }

        return collect($rule->requiredSpecialNeeds())
            ->every(fn (string $need) => in_array($need, $plan->specialNeeds, true));
    }

    /** @return Collection<int, array{0:Product,1:ProductVariant}> */
    private function candidates(TripRecommendationRule $rule, TripServiceMode $mode): Collection
    {
        if ($rule->variant !== null) {
            return collect([[$rule->variant->product, $rule->variant]]);
        }

        if ($rule->product !== null) {
            return $rule->product->variants
                ->sortBy(fn (ProductVariant $variant) => [$variant->sort_order, $variant->getKey()])
                ->map(fn (ProductVariant $variant) => [$rule->product, $variant])
                ->values();
        }

        $query = Product::query()
            ->active()
            ->with(['category', 'variants' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('id')])
            ->orderBy('id');

        if ($rule->category !== null) {
            $query->where('category_id', $rule->product_category_id);
        }

        if ($mode->supportsRental() && ! $mode->supportsSale()) {
            $query->rental();
        } elseif ($mode->supportsSale() && ! $mode->supportsRental()) {
            $query->sale();
        }

        return $query->get()
            ->flatMap(fn (Product $product) => $product->variants->map(fn (ProductVariant $variant) => [$product, $variant]))
            ->values();
    }

    private function eligible(Product $product, ProductVariant $variant, TripServiceMode $mode): bool
    {
        if ($product->status !== ProductStatus::Active || ! $variant->is_active) {
            return false;
        }

        return match ($mode) {
            TripServiceMode::Sewa => $product->supportsRental(),
            TripServiceMode::Beli => $product->supportsSale() && $this->salePrice($variant) > 0,
            TripServiceMode::Fleksibel, TripServiceMode::Semua => $product->supportsRental() || ($product->supportsSale() && $this->salePrice($variant) > 0),
        };
    }

    private function quantity(TripRecommendationRule $rule, TripPlanData $plan): int
    {
        if ($rule->quantity_per_participant) {
            return max(1, min(99, (int) $rule->recommended_quantity * $plan->participants));
        }

        return max(1, min(99, (int) $rule->recommended_quantity));
    }

    private function reason(TripRecommendationRule $rule, TripPlanData $plan): string
    {
        $template = trim((string) $rule->reason_template);

        if ($template === '') {
            $template = 'Cocok untuk aktivitas :activity dengan :participants peserta selama :duration hari.';
        }

        return strtr($template, [
            ':activity' => $this->activityLabel($plan->activity),
            ':participants' => (string) $plan->participants,
            ':duration' => (string) $plan->durationDays,
            ':destination' => $plan->destination,
        ]);
    }

    private function activityLabel(string $activity): string
    {
        return TripActivity::tryFrom($activity)?->label() ?? $activity;
    }

    private function salePrice(ProductVariant $variant): int
    {
        return Money::toInt($variant->effectiveSalePrice());
    }
}
