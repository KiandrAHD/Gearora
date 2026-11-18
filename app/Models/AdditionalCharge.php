<?php

namespace App\Models;

use App\Enums\AdditionalChargeStatus;
use App\Enums\AdditionalChargeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdditionalCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'charge_code',
        'rental_order_id',
        'rental_asset_id',
        'inspection_id',
        'type',
        'reason',
        'amount',
        'deposit_offset_amount',
        'outstanding_amount',
        'evidence_path',
        'status',
        'payment_status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => AdditionalChargeType::class,
            'status' => AdditionalChargeStatus::class,
            'amount' => 'integer',
            'deposit_offset_amount' => 'integer',
            'outstanding_amount' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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
     * @return BelongsTo<RentalAsset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(RentalAsset::class, 'rental_asset_id');
    }

    /**
     * @return BelongsTo<Inspection, $this>
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }
}
