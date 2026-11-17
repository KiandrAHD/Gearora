<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'attributes',
        'rental_price_per_day',
        'sale_price',
        'stock_quantity',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'rental_price_per_day' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active' => 'boolean',
            'stock_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<RentalAsset, $this>
     */
    public function rentalAssets(): HasMany
    {
        return $this->hasMany(RentalAsset::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<ProductVariant>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function effectiveRentalPrice(): ?string
    {
        return $this->rental_price_per_day ?? $this->product?->rental_price_per_day;
    }

    public function effectiveSalePrice(): ?string
    {
        return $this->sale_price ?? $this->product?->sale_price;
    }
}
