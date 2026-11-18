<?php

namespace App\Models;

use App\Enums\InspectionResult;
use App\Enums\InspectionStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_asset_id',
        'rental_order_id',
        'rental_asset_assignment_id',
        'stage',
        'condition_before',
        'condition_after',
        'completeness_status',
        'damage_description',
        'missing_items',
        'result',
        'inspected_by',
        'inspected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => InspectionStage::class,
            'result' => InspectionResult::class,
            'missing_items' => 'array',
            'inspected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RentalAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(RentalAsset::class, 'rental_asset_id');
    }

    /**
     * @return BelongsTo<RentalOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }

    /**
     * @return BelongsTo<RentalAssetAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RentalAssetAssignment::class, 'rental_asset_assignment_id');
    }

    /**
     * @return HasMany<InspectionPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }
}
