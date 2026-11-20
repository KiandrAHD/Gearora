<?php

namespace App\Enums;

/**
 * Level pengalaman peserta Trip Planner. Dipakai untuk memfilter aturan
 * `required_experience_levels` (JSON nullable pada `trip_recommendation_rules`).
 */
enum ExperienceLevel: string
{
    case Pemula = 'pemula';
    case Menengah = 'menengah';
    case Mahir = 'mahir';

    public function label(): string
    {
        return match ($this) {
            self::Pemula => 'Pemula',
            self::Menengah => 'Menengah',
            self::Mahir => 'Mahir',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Pemula => 'Baru memulai; butuh panduan dan gear dasar.',
            self::Menengah => 'Pernah beberapa kali trip; nyaman dengan gear standar.',
            self::Mahir => 'Berpengalaman; paham teknik dan gear lanjutan.',
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
