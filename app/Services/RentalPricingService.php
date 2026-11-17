<?php

namespace App\Services;

use App\Data\PricingQuote;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\RentalPeriod;
use InvalidArgumentException;

/**
 * Perhitungan biaya rental di server. Harga final tidak pernah diambil dari frontend.
 */
class RentalPricingService
{
    public function durationDays(RentalPeriod $period): int
    {
        return $period->days();
    }

    /**
     * Jumlah deposit satu unit produk sesuai kebijakan (RENTAL_POLICY.md §5).
     */
    public function unitDeposit(Product $product, ?ProductVariant $variant = null): string
    {
        if (! $product->requires_deposit) {
            return '0.00';
        }

        $configured = $product->deposit_amount;

        if ($configured !== null && (float) $configured > 0) {
            return $this->money((float) $configured);
        }

        $dailyRate = (float) ($variant?->effectiveRentalPrice() ?? $product->rental_price_per_day ?? 0);
        $config = config('gearora.deposit');

        $amount = $dailyRate * ((float) $config['percentage_of_daily_rate'] / 100);
        $amount = max($amount, (float) $config['minimum']);
        $amount = min($amount, (float) $config['maximum']);

        $rounding = max(1, (int) $config['rounding']);
        $amount = ceil($amount / $rounding) * $rounding;

        return $this->money($amount);
    }

    /**
     * @param  array<int, array{variant: ProductVariant, quantity: int}>  $lines
     */
    public function quote(array $lines, RentalPeriod $period): PricingQuote
    {
        $days = $period->days();
        $subtotal = 0.0;
        $depositTotal = 0.0;
        $rows = [];

        foreach ($lines as $line) {
            $variant = $line['variant'];
            $quantity = max(1, (int) $line['quantity']);
            $unitPrice = (float) ($variant->effectiveRentalPrice() ?? 0);

            if ($unitPrice <= 0) {
                throw new InvalidArgumentException(
                    "Produk {$variant->name} tidak memiliki harga sewa per hari yang valid."
                );
            }

            $lineTotal = $unitPrice * $quantity * $days;
            $product = $variant->product;

            $lineDeposit = $product !== null
                ? (float) $this->unitDeposit($product, $variant) * $quantity
                : 0.0;

            $subtotal += $lineTotal;
            $depositTotal += $lineDeposit;

            $rows[] = [
                'product_id' => $variant->product_id,
                'variant_id' => $variant->getKey(),
                'product_name' => $product?->name ?? $variant->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'unit_price' => $this->money($unitPrice),
                'quantity' => $quantity,
                'duration_days' => $days,
                'line_total' => $this->money($lineTotal),
                'deposit' => $this->money($lineDeposit),
            ];
        }

        return new PricingQuote(
            period: $period,
            lines: $rows,
            subtotal: $this->money($subtotal),
            depositTotal: $this->money($depositTotal),
            total: $this->money($subtotal + $depositTotal),
        );
    }

    private function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }
}
