<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Nilai periode rental yang dinormalisasi dari tanggal mulai dan tanggal kembali.
 *
 * Kebijakan rental (RENTAL_POLICY.md §2) memakai model interval semi-terbuka
 * `[start_date, end_date)` dengan durasi minimum 1 hari. Kasus `start_date = end_date`
 * akan menghasilkan interval kosong, sehingga periode blokir unit dinormalisasi menjadi
 * `max(end_date, start_date + 1 hari)`. Keputusan ini dicatat pada ADR_RENTAL_INTERVAL.md.
 */
final class RentalPeriod
{
    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function from(CarbonInterface|string $start, CarbonInterface|string $end): self
    {
        $startDate = $start instanceof CarbonInterface
            ? CarbonImmutable::instance($start)->startOfDay()
            : CarbonImmutable::parse($start)->startOfDay();

        $endDate = $end instanceof CarbonInterface
            ? CarbonImmutable::instance($end)->startOfDay()
            : CarbonImmutable::parse($end)->startOfDay();

        return new self($startDate, $endDate);
    }

    /**
     * Durasi sewa dalam hari, minimum 1 hari.
     */
    public function days(): int
    {
        return max(1, (int) $this->start->diffInDays($this->end));
    }

    /**
     * Batas blokir unit (eksklusif). Selalu lebih besar dari tanggal mulai.
     */
    public function blockingEnd(): CarbonImmutable
    {
        $minimumEnd = $this->start->addDay();

        return $this->end->greaterThan($minimumEnd) ? $this->end : $minimumEnd;
    }

    public function startDate(): string
    {
        return $this->start->toDateString();
    }

    public function endDate(): string
    {
        return $this->end->toDateString();
    }

    public function blockingEndDate(): string
    {
        return $this->blockingEnd()->toDateString();
    }

    public function isSameDay(): bool
    {
        return $this->start->equalTo($this->end);
    }

    public function overlaps(self $other): bool
    {
        return $this->start->lessThan($other->blockingEnd())
            && $this->blockingEnd()->greaterThan($other->start);
    }

    /**
     * Validasi periode terhadap kebijakan rental.
     *
     * @param  array{min_days:int, max_days:int, horizon_days:int, enforce_horizon:bool}  $policy
     */
    public function assertValid(array $policy): void
    {
        if ($this->end->lessThan($this->start)) {
            throw new InvalidArgumentException('Tanggal pengembalian tidak boleh sebelum tanggal mulai.');
        }

        if ($this->days() < $policy['min_days']) {
            throw new InvalidArgumentException(
                "Durasi sewa minimum {$policy['min_days']} hari."
            );
        }

        if ($this->days() > $policy['max_days']) {
            throw new InvalidArgumentException(
                "Durasi sewa maksimum {$policy['max_days']} hari."
            );
        }

        if ($policy['enforce_horizon']) {
            $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
            $horizon = $today->addDays($policy['horizon_days']);

            if ($this->start->lessThan($today)) {
                throw new InvalidArgumentException('Tanggal mulai tidak boleh di masa lalu.');
            }

            if ($this->start->greaterThan($horizon)) {
                throw new InvalidArgumentException(
                    "Pemesanan hanya dapat dilakukan maksimal {$policy['horizon_days']} hari ke depan."
                );
            }
        }
    }
}
