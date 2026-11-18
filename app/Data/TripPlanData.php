<?php

namespace App\Data;

use App\Enums\TripServiceMode;

/** Input Trip Planner yang sudah lolos Form Request. */
final readonly class TripPlanData
{
    /**
     * @param  array<int, string>  $specialNeeds
     */
    public function __construct(
        public string $activity,
        public string $destination,
        public ?string $location,
        public ?string $description,
        public int $durationDays,
        public int $participants,
        public array $specialNeeds,
        public TripServiceMode $mode,
        public ?string $startDate = null,
        public ?string $endDate = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            activity: (string) $data['activity'],
            destination: trim((string) $data['destination']),
            location: filled($data['location'] ?? null) ? trim((string) $data['location']) : null,
            description: filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            durationDays: (int) $data['duration_days'],
            participants: (int) $data['participants'],
            specialNeeds: array_values($data['special_needs'] ?? []),
            mode: TripServiceMode::from((string) $data['mode']),
            startDate: filled($data['start_date'] ?? null) ? (string) $data['start_date'] : null,
            endDate: filled($data['end_date'] ?? null) ? (string) $data['end_date'] : null,
        );
    }
}
