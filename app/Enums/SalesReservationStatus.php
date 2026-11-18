<?php

namespace App\Enums;

enum SalesReservationStatus: string
{
    case Reserved = 'reserved';
    case Consumed = 'consumed';
    case Released = 'released';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Reserved => 'Direservasi',
            self::Consumed => 'Terpakai',
            self::Released => 'Dilepas',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /** Reservasi yang masih menahan stok. */
    public function isActive(): bool
    {
        return $this === self::Reserved;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
