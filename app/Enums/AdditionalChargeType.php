<?php

namespace App\Enums;

enum AdditionalChargeType: string
{
    case LateFee = 'late_fee';
    case Damage = 'damage';
    case Lost = 'lost';
    case MissingAccessory = 'missing_accessory';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LateFee => 'Denda keterlambatan',
            self::Damage => 'Kerusakan',
            self::Lost => 'Kehilangan',
            self::MissingAccessory => 'Aksesori hilang',
            self::Other => 'Lainnya',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
