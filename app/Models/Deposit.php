<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_order_id',
        'customer_id',
        'required_amount',
        'collected_amount',
        'deducted_amount',
        'refunded_amount',
        'forfeited_amount',
        'outstanding_amount',
        'status',
        'held_at',
        'settled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'required_amount' => 'integer',
            'collected_amount' => 'integer',
            'deducted_amount' => 'integer',
            'refunded_amount' => 'integer',
            'forfeited_amount' => 'integer',
            'outstanding_amount' => 'integer',
            'status' => DepositStatus::class,
            'held_at' => 'datetime',
            'settled_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return HasMany<DepositTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(DepositTransaction::class);
    }

    /** Saldo deposit yang masih ditahan (collected - deducted - refunded - forfeited). */
    public function balance(): int
    {
        return (int) $this->collected_amount
            - (int) $this->deducted_amount
            - (int) $this->refunded_amount
            - (int) $this->forfeited_amount;
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [DepositStatus::Settled, DepositStatus::Forfeited], true);
    }
}
