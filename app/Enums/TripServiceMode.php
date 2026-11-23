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
    case Fleksibel = 'fleksibel';
    case Semua = 'semua';

    public function label(): string
    {
        return match ($this) {
            self::Sewa => 'Sewa',
            self::Beli => 'Beli',
            self::Fleksibel => 'Fleksibel (pilih per item)',
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
        return [self::Sewa->value, self::Beli->value, self::Fleksibel->value];
    }

    /**
     * Nilai mode yang valid untuk sebuah aturan rekomendasi Admin.
     *
     * `fleksibel` adalah pilihan pelanggan, bukan mode aturan; aturan yang ingin
     * mencakup keduanya memakai `semua`.
     *
     * @return array<int, string>
     */
    public static function ruleValues(): array
    {
        return [self::Sewa->value, self::Beli->value, self::Semua->value];
    }

    /**
     * Apakah aturan bermode ini cocok dengan mode yang diminta pelanggan.
     *
     * - Aturan `semua` selalu cocok.
     * - Mode `fleksibel` (pelanggan memilih sewa/beli per item) menerima aturan
     *   sewa maupun beli karena item tetap divalidasi ulang saat checkout.
     * - Mode tegas `sewa`/`beli` hanya cocok dengan aturan yang sama atau `semua`.
     */
    public function matches(self $requested): bool
    {
        if ($this === self::Semua || $this === $requested) {
            return true;
        }

        return $requested === self::Fleksibel;
    }
}
