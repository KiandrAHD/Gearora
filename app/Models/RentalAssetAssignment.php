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
        'assigned_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
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
}
