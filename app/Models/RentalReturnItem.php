<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalReturnItem extends Model
{
    protected $fillable = [
        'rental_return_id',
        'rental_asset_assignment_id',
        'rental_asset_id',
        'asset_code_snapshot',
        'condition_notes',
        'customer_acknowledged',
    ];

    protected function casts(): array
    {
        return [
            'customer_acknowledged' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RentalReturn, $this>
     */
    public function rentalReturn(): BelongsTo
    {
        return $this->belongsTo(RentalReturn::class);
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
