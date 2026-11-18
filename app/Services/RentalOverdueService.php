<?php

namespace App\Services;

use App\Data\OverdueResult;
use App\Models\RentalOrder;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Perhitungan keterlambatan & denda berdasarkan RENTAL_POLICY.md §4.
 *
 * Aturan:
 * - Batas return 20:00 WIB, grace 60 menit (sampai 21:00 WIB).
 * - Lewat grace dihitung 1 hari penuh, hari berikutnya per 24 jam.
 * - Denda 100% harga sewa harian per hari keterlambatan.
 * - Lewat 7 hari → proses barang hilang.
 */
class RentalOverdueService
{
    public function forOrder(RentalOrder $order, CarbonInterface|string|null $now = null): OverdueResult
    {
        $tz = config('app.timezone');
        $at = $now === null
            ? CarbonImmutable::now($tz)
            : ($now instanceof CarbonInterface ? CarbonImmutable::instance($now) : CarbonImmutable::parse($now, $tz));

        [$deadlineHour, $deadlineMinute] = $this->parseTime((string) config('gearora.rental.return_deadline', '20:00'));

        $deadline = CarbonImmutable::parse($order->end_date->toDateString(), $tz)
            ->setTime($deadlineHour, $deadlineMinute);

        $graceMinutes = (int) config('gearora.late_fee.grace_minutes', 60);
        $graceEnd = $deadline->addMinutes($graceMinutes);

        if ($at->lessThanOrEqualTo($graceEnd)) {
            return new OverdueResult(false, 0, 0, false, $deadline, null);
        }

        $minutesPast = $graceEnd->diffInMinutes($at);
        $daysLate = 1 + intdiv((int) $minutesPast, 24 * 60);

        $maxDays = (int) config('gearora.late_fee.max_days', 7);
        $isLost = $daysLate > $maxDays;

        $fee = $isLost ? 0 : $this->dailyRate($order) * $daysLate;

        return new OverdueResult(
            isOverdue: true,
            daysLate: $daysLate,
            lateFee: $fee,
            isLost: $isLost,
            deadline: $deadline,
            note: $isLost
                ? "Keterlambatan melebihi {$maxDays} hari; order masuk proses barang hilang."
                : "Terlambat {$daysLate} hari; denda Rp".number_format($fee, 0, ',', '.').'.',
        );
    }

    public function isOverdue(RentalOrder $order, CarbonInterface|string|null $now = null): bool
    {
        return $this->forOrder($order, $now)->isOverdue;
    }

    /** Harga sewa satu hari (dari snapshot order, bukan katalog terkini). */
    public function dailyRate(RentalOrder $order): int
    {
        $days = max(1, (int) $order->duration_days);

        return (int) intdiv(Money::toInt($order->subtotal), $days);
    }

    /** @return array{0:int,1:int} */
    private function parseTime(string $time): array
    {
        if (! str_contains($time, ':')) {
            return [20, 0];
        }

        [$hour, $minute] = explode(':', $time, 2);

        return [(int) $hour, (int) $minute];
    }
}
