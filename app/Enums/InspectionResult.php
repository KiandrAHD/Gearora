<?php

namespace App\Enums;

enum InspectionResult: string
{
    case Pass = 'pass';
    case Fail = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Lolos',
            self::Fail => 'Bermasalah',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
