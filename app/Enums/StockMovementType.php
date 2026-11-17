<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';
    case Addition = 'addition';
    case Adjustment = 'adjustment';
    case Damage = 'damage';
    case Lost = 'lost';
    case RentalOut = 'rental_out';
    case RentalReturn = 'rental_return';
    case Sale = 'sale';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Stok awal',
            self::Addition => 'Penambahan',
            self::Adjustment => 'Penyesuaian',
            self::Damage => 'Rusak',
            self::Lost => 'Hilang',
            self::RentalOut => 'Keluar rental',
            self::RentalReturn => 'Kembali rental',
            self::Sale => 'Penjualan',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
