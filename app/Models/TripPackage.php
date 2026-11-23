<?php

namespace App\Models;

use App\Enums\ExperienceLevel;
use App\Enums\PaymentStatus;
use App\Enums\TripPackageStatus;
use App\Enums\TripServiceMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'checkout_key',
        'customer_id',
        'activity',
        'destination',
        'location',
        'experience_level',
        'duration_days',
        'participants',
        'mode',
        'start_date',
        'end_date',
        'departure_time',
        'status',
        'rental_order_id',
        'sales_order_id',
        'plan_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'experience_level' => ExperienceLevel::class,
            'mode' => TripServiceMode::class,
            'status' => TripPackageStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'duration_days' => 'integer',
            'participants' => 'integer',
            'plan_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /** @return BelongsTo<RentalOrder, $this> */
    public function rentalOrder(): BelongsTo
    {
        return $this->belongsTo(RentalOrder::class);
    }

    /** @return BelongsTo<SalesOrder, $this> */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Status pembayaran pesanan sewa, diambil dari pembayaran terakhir.
     * Null berarti belum ada upaya pembayaran.
     */
    public function rentalPaymentStatus(): ?PaymentStatus
    {
        return $this->rentalOrder?->latestPayment?->status;
    }

    /**
     * Status pembayaran pesanan beli. Mengutamakan pembayaran terakhir, lalu
     * jatuh ke kolom agregat `payment_status` pada pesanan.
     */
    public function salesPaymentStatus(): ?PaymentStatus
    {
        $order = $this->salesOrder;

        return $order?->latestPayment?->status ?? $order?->payment_status;
    }

    /**
     * Kebutuhan khusus tersimpan di snapshot agar tidak berubah setelah checkout.
     *
     * @return array<int, string>
     */
    public function specialNeeds(): array
    {
        $needs = $this->plan_snapshot['special_needs'] ?? [];

        return is_array($needs) ? array_values($needs) : [];
    }

    public function planDescription(): ?string
    {
        $description = $this->plan_snapshot['description'] ?? null;

        return filled($description) ? (string) $description : null;
    }
}
