<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'product_variant_id',
        'rental_asset_id',
        'type',
        'quantity_before',
        'quantity_after',
        'status_before',
        'status_after',
        'reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'created_at' => 'datetime',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<RentalAsset, $this>
     */
    public function rentalAsset(): BelongsTo
    {
        return $this->belongsTo(RentalAsset::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
