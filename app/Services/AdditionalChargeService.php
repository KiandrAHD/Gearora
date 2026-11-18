<?php

namespace App\Services;

use App\Enums\AdditionalChargeStatus;
use App\Enums\AdditionalChargeType;
use App\Models\AdditionalCharge;
use App\Models\Deposit;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Biaya tambahan (denda, kerusakan, kehilangan, aksesori).
 *
 * - Dibuat dengan status pending_approval; tidak otomatis menagih pelanggan.
 * - Disetujui Admin/Owner; potong deposit lebih dulu, sisa menjadi outstanding.
 */
class AdditionalChargeService
{
    public function __construct(
        private readonly DepositService $deposits,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array{type:string,reason:string,amount:int,asset_id?:int,inspection_id?:int,evidence_path?:string,notes?:string}  $data
     */
    public function create(RentalOrder $order, array $data, User $actor): AdditionalCharge
    {
        $amount = (int) ($data['amount'] ?? 0);

        if ($amount <= 0) {
            throw new RuntimeException('Nominal biaya tambahan harus lebih dari nol.');
        }

        if (blank($data['reason'] ?? null)) {
            throw new RuntimeException('Alasan biaya tambahan wajib diisi.');
        }

        $charge = AdditionalCharge::query()->create([
            'charge_code' => $this->code(),
            'rental_order_id' => $order->getKey(),
            'rental_asset_id' => $data['asset_id'] ?? null,
            'inspection_id' => $data['inspection_id'] ?? null,
            'type' => AdditionalChargeType::from($data['type']),
            'reason' => $data['reason'],
            'amount' => $amount,
            'outstanding_amount' => $amount,
            'evidence_path' => $data['evidence_path'] ?? null,
            'status' => AdditionalChargeStatus::PendingApproval,
            'payment_status' => 'unpaid',
            'created_by' => $actor->getKey(),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->logger->log(
            'charge.created',
            $charge,
            "Biaya tambahan {$charge->charge_code} (Rp".number_format($amount, 0, ',', '.').") diajukan: {$data['reason']}.",
            ['amount' => $amount, 'type' => $data['type']],
            $actor,
        );

        return $charge;
    }

    public function reject(AdditionalCharge $charge, User $actor, string $note): AdditionalCharge
    {
        if ($charge->status !== AdditionalChargeStatus::PendingApproval) {
            throw new RuntimeException('Hanya biaya berstatus menunggu persetujuan yang dapat ditolak.');
        }

        $charge->forceFill([
            'status' => AdditionalChargeStatus::Rejected,
            'rejected_by' => $actor->getKey(),
            'rejected_at' => now(),
            'notes' => $note,
        ])->save();

        $this->logger->log('charge.rejected', $charge, "Biaya tambahan {$charge->charge_code} ditolak.", ['note' => $note], $actor);

        return $charge->refresh();
    }

    /**
     * Setujui biaya & potong dari deposit lebih dulu. Sisa menjadi outstanding.
     */
    public function approve(AdditionalCharge $charge, User $approver): AdditionalCharge
    {
        if ($charge->status !== AdditionalChargeStatus::PendingApproval) {
            throw new RuntimeException('Hanya biaya berstatus menunggu persetujuan yang dapat disetujui.');
        }

        return DB::transaction(function () use ($charge, $approver) {
            $charge = AdditionalCharge::query()->whereKey($charge->getKey())->lockForUpdate()->firstOrFail();
            $order = RentalOrder::query()->whereKey($charge->rental_order_id)->lockForUpdate()->firstOrFail();
            $deposit = Deposit::query()->where('rental_order_id', $order->getKey())->lockForUpdate()->first();

            $offset = 0;

            if ($deposit !== null && $deposit->balance() > 0) {
                $offset = $this->deposits->deduct(
                    $deposit,
                    (int) $charge->amount,
                    "Pemotongan untuk {$charge->charge_code}: {$charge->reason}",
                    $approver,
                    $approver,
                    $charge->evidence_path,
                );
            }

            $outstanding = (int) $charge->amount - $offset;

            $charge->forceFill([
                'status' => $outstanding <= 0
                    ? AdditionalChargeStatus::OffsetFromDeposit
                    : ($offset > 0 ? AdditionalChargeStatus::PartiallySettled : AdditionalChargeStatus::Approved),
                'deposit_offset_amount' => $offset,
                'outstanding_amount' => max(0, $outstanding),
                'payment_status' => $outstanding <= 0 ? 'settled' : 'unpaid',
                'approved_by' => $approver->getKey(),
                'approved_at' => now(),
            ])->save();

            $this->logger->log(
                'charge.approved',
                $charge,
                "Biaya tambahan {$charge->charge_code} disetujui. Potong deposit Rp".number_format($offset, 0, ',', '.').', sisa Rp'.number_format(max(0, $outstanding), 0, ',', '.').'.',
                ['offset' => $offset, 'outstanding' => $outstanding],
                $approver,
            );

            return $charge->refresh();
        });
    }

    /** Catat pembayaran tambahan atas outstanding biaya. */
    public function recordPayment(AdditionalCharge $charge, int $amount, User $actor): AdditionalCharge
    {
        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran harus lebih dari nol.');
        }

        if (! in_array($charge->status, [AdditionalChargeStatus::Approved, AdditionalChargeStatus::PartiallySettled], true)) {
            throw new RuntimeException('Biaya ini tidak dalam tahap yang dapat dibayar.');
        }

        return DB::transaction(function () use ($charge, $amount, $actor) {
            $charge = AdditionalCharge::query()->whereKey($charge->getKey())->lockForUpdate()->firstOrFail();

            $outstanding = max(0, (int) $charge->outstanding_amount - $amount);

            $charge->forceFill([
                'outstanding_amount' => $outstanding,
                'status' => $outstanding <= 0 ? AdditionalChargeStatus::Paid : AdditionalChargeStatus::PartiallySettled,
                'payment_status' => $outstanding <= 0 ? 'settled' : 'partial',
            ])->save();

            $this->logger->log('charge.paid', $charge, "Pembayaran biaya {$charge->charge_code} Rp".number_format($amount, 0, ',', '.').'.', ['amount' => $amount], $actor);

            return $charge->refresh();
        });
    }

    private function code(): string
    {
        return 'CHG-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
