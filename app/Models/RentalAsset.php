<?php

namespace App\Models;

use App\Enums\RentalAssetCondition;
use App\Enums\RentalAssetStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'asset_code',
        'condition',
        'status',
        'purchase_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'condition' => RentalAssetCondition::class,
            'status' => RentalAssetStatus::class,
            'purchase_date' => 'date',
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
     * @return HasMany<RentalAssetAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RentalAssetAssignment::class);
    }

    /**
     * @param  Builder<RentalAsset>  $query
     */
    public function scopeRentable(Builder $query): void
    {
        $query->whereIn('status', RentalAssetStatus::rentableValues());
    }

    public function isRentable(): bool
    {
        return $this->status->isRentable();
    }
}
