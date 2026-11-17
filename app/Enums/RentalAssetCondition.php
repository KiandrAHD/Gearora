<?php

namespace App\Enums;

enum RentalAssetCondition: string
{
    case Good = 'good';
    case Fair = 'fair';
    case NeedsRepair = 'needs_repair';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Baik',
            self::Fair => 'Cukup',
            self::NeedsRepair => 'Perlu perbaikan',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
