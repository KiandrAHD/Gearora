<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalAssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_order_id',
        'rental_order_item_id',
        'rental_asset_id',
        'status',
        'assigned_at',
        'handed_over_at',
        'returned_at',
        'released_at',
        'rental_handover_item_id',
        'rental_return_item_id',
        'current_inspection_id',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'returned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<RentalOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }

    /**
     * @return BelongsTo<RentalOrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(RentalOrderItem::class, 'rental_order_item_id');
    }

    /**
     * @return BelongsTo<RentalAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(RentalAsset::class, 'rental_asset_id');
    }

    /**
     * @return BelongsTo<RentalHandoverItem, $this>
     */
    public function handoverItem(): BelongsTo
    {
        return $this->belongsTo(RentalHandoverItem::class, 'rental_handover_item_id');
    }

    /**
     * @return BelongsTo<RentalReturnItem, $this>
     */
    public function returnItem(): BelongsTo
    {
        return $this->belongsTo(RentalReturnItem::class, 'rental_return_item_id');
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function currentInspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class, 'current_inspection_id');
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }
}
