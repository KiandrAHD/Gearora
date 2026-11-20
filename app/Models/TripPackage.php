<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use App\Enums\TripPackageStatus;
use App\Enums\TripServiceMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_id',
        'activity',
        'destination',
        'location',
        'experience_level',
        'duration_days',
        'participants',
        'mode',
        'start_date',
        'end_date',
        'status',
        'rental_order_id',
        'sales_order_id',
        'plan_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'experience_level' => ExperienceLevel::class,
            'mode' => TripServiceMode::class,
            'status' => TripPackageStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'participants' => 'integer',
            'plan_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<RentalOrder, $this> */
    public function rentalOrder(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class);
    }

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
