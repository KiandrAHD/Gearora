<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_order_id',
        'method',
        'amount',
        'rental_amount',
        'deposit_amount',
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
            'amount' => 'decimal:2',
            'rental_amount' => 'integer',
            'deposit_amount' => 'integer',
            'verified_at' => 'datetime',
            'settled_at' => 'datetime',
            'proof_submitted_at' => 'datetime',
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
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return HasMany<PaymentAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    /**
     * @return HasOne<PaymentAttempt, $this>
     */
    public function latestAttempt(): HasOne
    {
        return $this->hasOne(PaymentAttempt::class)->latestOfMany();
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * @return HasMany<PaymentProof, $this>
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function isVerified(): bool
    {
        return $this->status === PaymentStatus::Verified;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    /** Total Rupiah sebagai integer (dari kolom decimal). */
    public function amountRupiah(): int
    {
        return (int) round((float) $this->amount);
    }
}
