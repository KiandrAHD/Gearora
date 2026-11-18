<?php

namespace App\Enums;

/**
 * Mode perlengkapan Trip Planner. Menentukan produk yang layak direkomendasikan:
 * sewa, beli, atau keduanya (sentinel "semua" untuk aturan).
 */
enum TripServiceMode: string
{
    case Sewa = 'sewa';
    case Beli = 'beli';
    case Semua = 'semua';

    public function label(): string
    {
        return match ($this) {
            self::Sewa => 'Sewa',
            self::Beli => 'Beli',
            self::Semua => 'Sewa & Beli',
        };
    }

    public function supportsRental(): bool
    {
        return $this !== self::Beli;
    }

    public function supportsSale(): bool
    {
        return $this !== self::Sewa;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<int, string> Nilai mode yang dapat dipilih pelanggan. */
    public static function customerValues(): array
    {
        return [self::Sewa->value, self::Beli->value];
    }

    /** Apakah aturan bermode ini cocok dengan mode yang diminta pelanggan. */
    public function matches(self $requested): bool
    {
        return $this === self::Semua || $this === $requested;
    }
}
