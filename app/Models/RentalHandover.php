<?php

namespace App\Models;

use App\Enums\HandoverStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalHandover extends Model
{
    protected $fillable = [
        'handover_code',
        'rental_order_id',
        'handed_over_at',
        'handled_by',
        'customer_identity_type',
        'identity_checked',
        'customer_acknowledged',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'handed_over_at' => 'datetime',
            'identity_checked' => 'boolean',
            'customer_acknowledged' => 'boolean',
            'status' => HandoverStatus::class,
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
     * @return HasMany<RentalHandoverItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalHandoverItem::class);
    }
}
