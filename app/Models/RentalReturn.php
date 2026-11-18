<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalReturn extends Model
{
    protected $fillable = [
        'return_code',
        'rental_order_id',
        'returned_at',
        'received_by',
        'customer_acknowledged',
        'status',
        'general_notes',
    ];

    protected function casts(): array
    {
        return [
            'returned_at' => 'datetime',
            'customer_acknowledged' => 'boolean',
            'status' => ReturnStatus::class,
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
     * @return HasMany<RentalReturnItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalReturnItem::class);
    }
}
