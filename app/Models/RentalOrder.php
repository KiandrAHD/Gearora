<?php

namespace App\Models;

use App\Contracts\PayableOrder;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RentalOrder extends Model implements PayableOrder
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
     * @return HasOne<Deposit, $this>
     */
    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class);
    }

    /**
     * @return HasMany<RentalHandover, $this>
     */
    public function handovers(): HasMany
    {
        return $this->hasMany(RentalHandover::class);
    }

    /**
     * @return HasMany<RentalReturn, $this>
     */
    public function rentalReturns(): HasMany
    {
        return $this->hasMany(RentalReturn::class);
    }

    /**
     * @return HasMany<Inspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    /**
     * @return HasMany<AdditionalCharge, $this>
     */
    public function additionalCharges(): HasMany
    {
        return $this->hasMany(AdditionalCharge::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
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

    /** Order memiliki pembayaran yang sudah settled (verified atau refund final). */
    public function hasVerifiedPayment(): bool
    {
        return $this->payments()
            ->where('status', PaymentStatus::Verified->value)
            ->exists();
    }

    /** Apakah deposit sudah terkumpul sesuai kewajiban. */
    public function depositIsCollected(): bool
    {
        $deposit = $this->deposit;

        return $deposit !== null
            && $deposit->collected_amount >= $deposit->required_amount;
    }

    /** Apakah seluruh unit sudah dikembalikan (setiap assignment memiliki returned_at). */
    public function allUnitsReturned(): bool
    {
        $released = $this->assignments()
            ->whereNull('released_at')
            ->whereNotNull('returned_at')
            ->count();

        return $released === 0 && $this->assignments()->count() > 0;
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
        $items = [];

        foreach ($this->items as $item) {
            $items[] = [
                'id' => 'rental-'.$item->getKey(),
                'price' => (int) round((float) $item->rental_price_snapshot),
                'quantity' => (int) $item->quantity * (int) $item->duration_days,
                'name' => mb_substr($item->product_name_snapshot.' - '.$item->variant_name_snapshot, 0, 50),
            ];
        }

        if ((int) $this->deposit_total > 0) {
            $items[] = [
                'id' => 'deposit-'.$this->getKey(),
                'price' => (int) round((float) $this->deposit_total),
                'quantity' => 1,
                'name' => 'Deposit jaminan rental',
            ];
        }

        return $items;
    }
}
