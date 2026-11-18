<?php

namespace App\Enums;

enum DepositStatus: string
{
    case Pending = 'pending';
    case Held = 'held';
    case PartiallySettled = 'partially_settled';
    case Settled = 'settled';
    case Forfeited = 'forfeited';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu diterima',
            self::Held => 'Ditahan',
            self::PartiallySettled => 'Selesai sebagian',
            self::Settled => 'Selesai',
            self::Forfeited => 'Hangus',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
