<?php

namespace App\Models;

use App\Enums\PaymentAttemptStatus;
use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_payment_id',
        'provider',
        'provider_order_id',
        'provider_transaction_id',
        'provider_payment_type',
        'status',
        'fraud_status',
        'gross_amount',
        'currency',
        'snap_token',
        'redirect_url',
        'idempotency_key',
        'expires_at',
        'paid_at',
        'failed_at',
        'provider_metadata',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'status' => PaymentAttemptStatus::class,
            'gross_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'provider_metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<SalesPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(SalesPayment::class, 'sales_payment_id');
    }

    /** Sales order pemilik attempt (melalui payment). */
    public function salesOrder(): ?SalesOrder
    {
        return $this->payment?->order;
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }
}
