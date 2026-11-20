<?php

namespace App\Enums;

/**
 * Status paket Trip Planner. Status agregat dari RentalOrder dan Sales Order
 * terkait; paket TIDAK menyimpan nominal pembayaran (harga selalu dihitung
 * pada order masing-masing).
 */
enum TripPackageStatus: string
{
    case Draft = 'draft';
    case AwaitingPayment = 'awaiting_payment';
    case PartiallyConfirmed = 'partially_confirmed';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::AwaitingPayment => 'Menunggu pembayaran',
            self::PartiallyConfirmed => 'Sebagian terkonfirmasi',
            self::Confirmed => 'Terkonfirmasi',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
