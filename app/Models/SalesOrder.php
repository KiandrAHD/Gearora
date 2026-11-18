<?php

namespace App\Models;

use App\Contracts\PayableOrder;
use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model implements PayableOrder
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_id',
        'subtotal',
        'discount_total',
        'total',
        'payment_status',
        'order_status',
        'notes',
        'confirmed_at',
        'pickup_ready_at',
        'completed_at',
        'cancelled_at',
        'expires_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_status' => SalesOrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'pickup_ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * @return HasMany<SalesStockReservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(SalesStockReservation::class);
    }

    /**
     * @return HasMany<SalesPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(SalesPayment::class);
    }

    /**
     * @return HasOne<SalesPayment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(SalesPayment::class)->latestOfMany();
    }

    /**
     * @param  Builder<SalesOrder>  $query
     */
    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function hasVerifiedPayment(): bool
    {
        return $this->payments()->where('status', PaymentStatus::Verified->value)->exists();
    }

    public function totalRupiah(): int
    {
        return Money::toInt($this->total);
    }

    // -- PayableOrder ---------------------------------------------------------

    public function payableCode(): string
    {
        return (string) $this->order_code;
    }

    public function payableCustomer(): ?User
    {
        return $this->customer;
    }

    public function payableItemDetails(): array
    {
        return $this->items
            ->map(fn (SalesOrderItem $item) => [
                'id' => 'sale-'.$item->getKey(),
                'price' => (int) $item->unit_price_snapshot,
                'quantity' => (int) $item->quantity,
                'name' => mb_substr($item->product_name_snapshot.' - '.$item->variant_name_snapshot, 0, 50),
            ])
            ->values()
            ->all();
    }
}
