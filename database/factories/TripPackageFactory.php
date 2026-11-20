<?php

namespace Database\Factories;

use App\Enums\ExperienceLevel;
use App\Enums\TripPackageStatus;
use App\Enums\TripServiceMode;
use App\Models\TripPackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TripPackage>
 */
class TripPackageFactory extends Factory
{
    protected $model = TripPackage::class;

    public function definition(): array
    {
        return [
            'code' => 'GRP-'.now()->format('Ymd').'-'.strtoupper(Str::random(4)),
            'checkout_key' => Str::uuid()->toString(),
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => User::factory(),
            'activity' => 'hiking',
            'destination' => 'Gunung Prau',
            'location' => 'Dieng',
            'experience_level' => ExperienceLevel::Pemula,
            'duration_days' => 2,
            'participants' => 2,
            'mode' => TripServiceMode::Sewa,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => TripPackageStatus::AwaitingPayment,
            'plan_snapshot' => ['destination' => 'Gunung Prau'],
        ];
    }
}
