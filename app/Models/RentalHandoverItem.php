<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalHandoverItem extends Model
{
    protected $fillable = [
        'rental_handover_id',
        'rental_asset_assignment_id',
        'rental_asset_id',
        'asset_code_snapshot',
        'accessories_checklist',
        'condition_notes',
        'customer_acknowledged',
    ];

    protected function casts(): array
    {
        return [
            'accessories_checklist' => 'array',
            'customer_acknowledged' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RentalHandover, $this>
     */
    public function handover(): BelongsTo
    {
        return $this->belongsTo(RentalHandover::class);
    }

    /**
     * @return BelongsTo<RentalAssetAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(RentalAssetAssignment::class, 'rental_asset_assignment_id');
    }

    /**
     * @return BelongsTo<RentalAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(RentalAsset::class);
    }
}
