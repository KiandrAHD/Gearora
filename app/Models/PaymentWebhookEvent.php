<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\WebhookProcessingStatus;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'provider',
        'provider_order_id',
        'provider_transaction_id',
        'event_hash',
        'notification_status',
        'processing_status',
        'payload_safe',
        'received_at',
        'processed_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'provider' => PaymentProvider::class,
            'processing_status' => WebhookProcessingStatus::class,
            'payload_safe' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
