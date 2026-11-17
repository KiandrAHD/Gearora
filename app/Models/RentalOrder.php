<?php

namespace App\Models;

use App\Enums\RentalOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RentalOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'customer_id',
        'start_date',
        'end_date',
        'blocking_end_date',
        'duration_days',
        'subtotal',
        'deposit_total',
        'total',
        'order_status',
        'notes',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'order_status' => RentalOrderStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'blocking_end_date' => 'date',
            'duration_days' => 'integer',
            'subtotal' => 'decimal:2',
            'deposit_total' => 'decimal:2',
            'total' => 'decimal:2',
            'cancelled_at' => 'datetime',
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
     * @return HasMany<RentalOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalOrderItem::class);
    }

    /**
     * @return HasMany<RentalAssetAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(RentalAssetAssignment::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @param  Builder<RentalOrder>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('order_status', array_map(
            fn (RentalOrderStatus $status) => $status->value,
            RentalOrderStatus::blockingStatuses()
        ));
    }

    /**
     * @param  Builder<RentalOrder>  $query
     */
    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    /**
     * Overlap memakai interval semi-terbuka yang sudah dinormalisasi:
     * `blocking_end_date = max(end_date, start_date + 1 hari)`.
     *
     * @param  Builder<RentalOrder>  $query
     */
    public function scopeOverlapping(Builder $query, string $startDate, string $blockingEndDate): void
    {
        $query->where('start_date', '<', $blockingEndDate)
            ->where('blocking_end_date', '>', $startDate);
    }

    public function isBlocking(): bool
    {
        return $this->order_status->blocksAvailability();
    }
}
