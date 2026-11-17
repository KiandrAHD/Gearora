<?php

namespace App\Enums;

enum RentalAssetStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case Damaged = 'damaged';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Reserved => 'Dipesan',
            self::Rented => 'Disewa',
            self::Maintenance => 'Perawatan',
            self::Damaged => 'Rusak',
            self::Lost => 'Hilang',
        };
    }

    /** Unit yang boleh dialokasikan ke rental baru. */
    public function isRentable(): bool
    {
        return in_array($this, [self::Available, self::Reserved], true);
    }

    /** @return array<int, string> */
    public static function rentableValues(): array
    {
        return array_values(array_map(
            fn (self $case) => $case->value,
            array_filter(self::cases(), fn (self $case) => $case->isRentable())
        ));
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
