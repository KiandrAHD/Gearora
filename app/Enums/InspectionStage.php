<?php

namespace App\Enums;

enum InspectionStage: string
{
    case Handover = 'handover';
    case Return = 'return';

    public function label(): string
    {
        return match ($this) {
            self::Handover => 'Inspeksi serah terima',
            self::Return => 'Inspeksi pengembalian',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
