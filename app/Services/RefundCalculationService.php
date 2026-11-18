<?php

namespace App\Services;

use App\Data\RefundBreakdown;
use App\Models\RentalOrder;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Perhitungan refund berdasarkan RENTAL_POLICY.md §7.
 *
 * Deposit selalu dikembalikan penuh sesuai kebijakan pembatalan; hanya biaya
 * sewa yang diprorata. Tidak menghitung ulang harga dari katalog terkini —
 * memakai snapshot pada order.
 */
class RefundCalculationService
{
    public function calculate(
        RentalOrder $order,
        CarbonInterface|string|null $at = null,
        ?int $rentalPercentOverride = null,
    ): RefundBreakdown {
        $rentalAmount = Money::toInt($order->subtotal);
        $depositAmount = Money::toInt($order->deposit_total);

        $percent = $rentalPercentOverride ?? $this->rentalRefundPercent($order, $at);

        $rentalRefund = (int) floor($rentalAmount * $percent / 100);
        $depositRefund = $depositAmount;
        $nonRefundable = $rentalAmount - $rentalRefund;

        return new RefundBreakdown(
            rentalAmount: $rentalAmount,
            depositAmount: $depositAmount,
            rentalRefund: $rentalRefund,
            depositRefund: $depositRefund,
            nonRefundable: $nonRefundable,
            total: $rentalRefund + $depositRefund,
            rentalPercent: $percent,
            policyNote: $this->note($percent),
        );
    }

    /**
     * Refund biaya sewa (%) berdasarkan waktu pembatalan relatif start_date.
     */
    public function rentalRefundPercent(RentalOrder $order, CarbonInterface|string|null $at = null): int
    {
        $cancelledAt = $at === null
            ? CarbonImmutable::now(config('app.timezone'))
            : ($at instanceof CarbonInterface ? CarbonImmutable::instance($at) : CarbonImmutable::parse($at));

        $start = CarbonImmutable::parse($order->start_date->toDateString(), config('app.timezone'))->startOfDay();

        $hoursBefore = $cancelledAt->diffInHours($start, false);

        $fullHours = (int) config('gearora.refund.full_refund_hours', 48);
        $halfHours = (int) config('gearora.refund.half_refund_hours', 24);

        if ($hoursBefore > $fullHours) {
            return 100;
        }

        if ($hoursBefore >= $halfHours) {
            return 50;
        }

        return 0;
    }

    private function note(int $percent): string
    {
        return match (true) {
            $percent >= 100 => 'Pembatalan >48 jam sebelum mulai: refund sewa 100%.',
            $percent >= 50 => 'Pembatalan 24–48 jam sebelum mulai: refund sewa 50%.',
            default => 'Pembatalan <24 jam / no-show: biaya sewa tidak dikembalikan.',
        };
    }
}
