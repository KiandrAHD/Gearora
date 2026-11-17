<?php

namespace App\Enums;

enum ProductType: string
{
    case Rental = 'rental';
    case Sale = 'sale';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Rental => 'Sewa',
            self::Sale => 'Beli',
            self::Both => 'Sewa & Beli',
        };
    }

    public function supportsRental(): bool
    {
        return $this !== self::Sale;
    }

    public function supportsSale(): bool
    {
        return $this !== self::Rental;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
