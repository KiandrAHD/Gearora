<?php

namespace App\Models;

use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Enums\RefundType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_code',
        'payment_id',
        'payment_attempt_id',
        'rental_order_id',
        'refund_amount',
        'rental_amount',
        'deposit_amount',
        'refund_method',
        'refund_type',
        'reason',
        'status',
        'provider_refund_id',
        'provider_reference',
        'requested_by',
        'approved_by',
        'processed_by',
        'approved_at',
        'processed_at',
        'evidence_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'refund_amount' => 'integer',
            'rental_amount' => 'integer',
            'deposit_amount' => 'integer',
            'refund_method' => RefundMethod::class,
            'refund_type' => RefundType::class,
            'status' => RefundStatus::class,
            'approved_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<PaymentAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PaymentAttempt::class, 'payment_attempt_id');
    }

    /**
     * @return BelongsTo<RentalOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class, 'rental_order_id');
    }
}
