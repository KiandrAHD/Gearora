<?php

namespace App\Models;

use App\Enums\DepositTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositTransaction extends Model
{
    protected $fillable = [
        'transaction_code',
        'deposit_id',
        'type',
        'amount',
        'balance_after',
        'reason',
        'evidence_path',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => DepositTransactionType::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Deposit, $this>
     */
    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }
}
