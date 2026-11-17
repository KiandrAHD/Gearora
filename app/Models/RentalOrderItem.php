<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_order_id',
        'product_id',
        'product_variant_id',
        'product_name_snapshot',
        'variant_name_snapshot',
        'rental_price_snapshot',
        'quantity',
        'duration_days',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'rental_price_snapshot' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
            'duration_days' => 'integer',
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
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
}
