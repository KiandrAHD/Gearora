<?php

namespace App\Models;

use App\Enums\SalesReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesStockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'sales_order_item_id',
        'product_variant_id',
        'quantity',
        'status',
        'reserved_at',
        'expires_at',
        'consumed_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SalesReservationStatus::class,
            'quantity' => 'integer',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    /**
     * @return BelongsTo<SalesOrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @param  Builder<SalesStockReservation>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', SalesReservationStatus::Reserved->value);
    }
}
