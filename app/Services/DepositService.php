<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\DepositTransactionType;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use App\Models\RentalOrder;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Ledger deposit: seluruh mutasi saldo harus melalui deposit_transactions (ADR-5.4).
 * Saldo tidak pernah diubah langsung tanpa transaksi.
 */
class DepositService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    /** Pastikan deposit record ada untuk order (idempotent). */
    public function ensureForOrder(RentalOrder $order): Deposit
    {
        $required = Money::toInt($order->deposit_total);

        return Deposit::query()->firstOrCreate(
            ['rental_order_id' => $order->getKey()],
            [
                'customer_id' => $order->customer_id,
                'required_amount' => $required,
                'outstanding_amount' => $required,
                'status' => DepositStatus::Pending,
            ],
        );
    }

    /**
     * Catat penerimaan deposit berdasarkan pembayaran yang settled.
     * Idempotent: tidak menambah saldo dua kali untuk sumber yang sama.
     */
    public function collect(Deposit $deposit, int $amount, string $reason, ?User $actor = null, ?string $reference = null): Deposit
    {
        if ($amount <= 0) {
            return $deposit;
        }

        return DB::transaction(function () use ($deposit, $amount, $reason, $actor, $reference) {
            $deposit = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            $collected = (int) $deposit->collected_amount;

            // Tidak menagih melebihi kewajiban.
            $remaining = max(0, (int) $deposit->required_amount - $collected);

            if ($remaining === 0) {
                return $deposit;
            }

            $apply = min($amount, $remaining);

            $this->transaction($deposit, DepositTransactionType::Collected, $apply, $reference ? $reason.' (#'.$reference.')' : $reason, [
                'approved_by' => $actor?->getKey(),
                'approved_at' => now(),
            ]);

            $deposit->forceFill([
                'collected_amount' => $collected + $apply,
                'outstanding_amount' => max(0, (int) $deposit->required_amount - ($collected + $apply)),
                'status' => DepositStatus::Held,
                'held_at' => $deposit->held_at ?? now(),
            ])->save();

            $this->logger->log(
                'deposit.collected',
                $deposit,
                "Deposit order {$deposit->order->order_code} diterima Rp".number_format($apply, 0, ',', '.').'.',
                ['amount' => $apply, 'reference' => $reference],
                $actor,
            );

            return $deposit->refresh();
        });
    }

    /**
     * Potong saldo deposit untuk biaya yang sudah disetujui.
     * Tidak boleh membuat saldo negatif; sisa menjadi outstanding liability.
     *
     * @return int nominal yang berhasil dipotong
     */
    public function deduct(Deposit $deposit, int $amount, string $reason, User $actor, ?User $approver = null, ?string $evidencePath = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($deposit, $amount, $reason, $actor, $approver, $evidencePath) {
            $deposit = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            $available = $deposit->balance();

            if ($available <= 0) {
                return 0;
            }

            $apply = min($amount, $available);

            $this->transaction($deposit, DepositTransactionType::Deduction, $apply, $reason, [
                'evidence_path' => $evidencePath,
                'created_by' => $actor->getKey(),
                'approved_by' => ($approver ?? $actor)->getKey(),
                'approved_at' => now(),
            ]);

            $deposit->forceFill([
                'deducted_amount' => (int) $deposit->deducted_amount + $apply,
                'status' => DepositStatus::PartiallySettled,
            ])->save();

            $this->logger->log(
                'deposit.deducted',
                $deposit,
                "Deposit order {$deposit->order->order_code} dipotong Rp".number_format($apply, 0, ',', '.').': '.$reason,
                ['amount' => $apply, 'reason' => $reason],
                $actor,
            );

            return $apply;
        });
    }

    /** Kembalikan saldo deposit ke pelanggan (bukti + aktor wajib). */
    public function refund(Deposit $deposit, int $amount, string $reason, User $actor, ?string $evidencePath = null): int
    {
        if ($amount <= 0) {
            return 0;
        }

        return DB::transaction(function () use ($deposit, $amount, $reason, $actor, $evidencePath) {
            $deposit = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

            $available = $deposit->balance();

            if ($available <= 0) {
                throw new RuntimeException('Saldo deposit tidak mencukupi untuk dikembalikan.');
            }

            $apply = min($amount, $available);

            $this->transaction($deposit, DepositTransactionType::Refund, $apply, $reason, [
                'evidence_path' => $evidencePath,
                'created_by' => $actor->getKey(),
                'approved_by' => $actor->getKey(),
                'approved_at' => now(),
            ]);

            $deposit->forceFill([
                'refunded_amount' => (int) $deposit->refunded_amount + $apply,
                'status' => DepositStatus::PartiallySettled,
            ])->save();

            $this->logger->log(
                'deposit.refunded',
                $deposit,
                "Deposit order {$deposit->order->order_code} dikembalikan Rp".number_format($apply, 0, ',', '.').'.',
                ['amount' => $apply],
                $actor,
            );

            return $apply;
        });
    }

    /** Tandai deposit hangus (hilang / pelanggan menolak kewajiban sah). */
    public function forfeit(Deposit $deposit, string $reason, User $actor): void
    {
        DB::transaction(function () use ($deposit, $reason, $actor) {
            $deposit = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();
            $available = $deposit->balance();

            if ($available > 0) {
                $this->transaction($deposit, DepositTransactionType::Forfeiture, $available, $reason, [
                    'created_by' => $actor->getKey(),
                    'approved_by' => $actor->getKey(),
                    'approved_at' => now(),
                ]);

                $deposit->forceFill([
                    'forfeited_amount' => (int) $deposit->forfeited_amount + $available,
                ])->save();
            }

            $deposit->forceFill(['status' => DepositStatus::Forfeited, 'settled_at' => now()])->save();

            $this->logger->log('deposit.forfeited', $deposit, "Deposit order {$deposit->order->order_code} hangus: {$reason}.", ['reason' => $reason], $actor);
        });
    }

    /** Selesaikan deposit bila tidak ada lagi saldo tertahan. */
    public function settle(Deposit $deposit): Deposit
    {
        $deposit->refresh();

        if ($deposit->balance() <= 0) {
            $deposit->forceFill(['status' => DepositStatus::Settled, 'settled_at' => now()])->save();
        }

        return $deposit;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function transaction(Deposit $deposit, DepositTransactionType $type, int $amount, string $reason, array $extra = []): DepositTransaction
    {
        $balanceAfter = $deposit->balance() + ($type->sign() * $amount);

        return DepositTransaction::query()->create(array_merge([
            'transaction_code' => $this->code(),
            'deposit_id' => $deposit->getKey(),
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reason' => $reason,
        ], $extra));
    }

    private function code(): string
    {
        return 'DPT-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
