<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Kewajiban pembayaran retail. Terpisah dari Payment (rental) agar tidak ada
 * rental_order_id palsu; agregat provider tetap dipertahankan via
 * sales_payment_attempts (lihat ADR_SHARED_PAYMENT_FOR_RETAIL.md).
 */
class SalesPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'method',
        'amount',
        'status',
        'proof_path',
        'reference',
        'customer_note',
        'verification_note',
        'verified_by',
        'verified_at',
        'settled_at',
        'proof_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'verified_at' => 'datetime',
            'settled_at' => 'datetime',
            'proof_submitted_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<SalesPaymentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(SalesPaymentAttempt::class);
    }

    /**
     * @return HasOne<SalesPaymentAttempt, $this>
     */
    public function latestAttempt(): HasOne
    {
        return $this->hasOne(SalesPaymentAttempt::class)->latestOfMany();
    }

    public function amountRupiah(): int
    {
        return (int) $this->amount;
    }
}
