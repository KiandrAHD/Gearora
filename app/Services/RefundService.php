<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Data\RefundBreakdown;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Enums\RefundType;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\RentalOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Refund sebagai transaksi tersendiri (bukan sekadar mengubah status payment).
 * Mendukung gateway otomatis (bila metode mendukung) dan refund manual.
 */
class RefundService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly RefundCalculationService $calculator,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Ajukan refund dari hasil perhitungan kebijakan pembatalan.
     */
    public function requestFromPolicy(RentalOrder $order, User $actor, ?string $reason = null): Refund
    {
        $breakdown = $this->calculator->calculate($order);

        return $this->request($order, $actor, $breakdown, RefundMethod::ManualTransfer, $reason);
    }

    public function request(
        RentalOrder $order,
        User $actor,
        RefundBreakdown $breakdown,
        RefundMethod $method,
        ?string $reason = null,
    ): Refund {
        if ($breakdown->total <= 0) {
            throw new RuntimeException('Tidak ada nominal yang dapat direfund.');
        }

        $payment = $this->settledPaymentFor($order);

        return DB::transaction(function () use ($order, $actor, $breakdown, $method, $reason, $payment) {
            $refund = Refund::query()->create([
                'refund_code' => $this->code(),
                'payment_id' => $payment->getKey(),
                'payment_attempt_id' => $payment->attempts()->whereIn('status', ['settlement', 'capture_accepted'])->latest()->value('id'),
                'rental_order_id' => $order->getKey(),
                'refund_amount' => $breakdown->total,
                'rental_amount' => $breakdown->rentalRefund,
                'deposit_amount' => $breakdown->depositRefund,
                'refund_method' => $method,
                'refund_type' => $breakdown->depositRefund > 0 && $breakdown->rentalRefund > 0 ? RefundType::Combined : ($breakdown->depositRefund > 0 ? RefundType::Deposit : RefundType::Rental),
                'reason' => $reason ?? $breakdown->policyNote,
                'status' => RefundStatus::PendingApproval,
                'requested_by' => $actor->getKey(),
            ]);

            $this->logger->log(
                'refund.requested',
                $refund,
                'Refund Rp'.number_format($breakdown->total, 0, ',', '.')." diajukan untuk order {$order->order_code}.",
                ['refund_code' => $refund->refund_code, 'amount' => $breakdown->total],
                $actor,
            );

            return $refund;
        });
    }

    public function approve(Refund $refund, User $actor): Refund
    {
        if ($refund->status !== RefundStatus::PendingApproval) {
            throw new RuntimeException('Hanya refund berstatus menunggu persetujuan yang dapat disetujui.');
        }

        $refund->forceFill([
            'status' => RefundStatus::Approved,
            'approved_by' => $actor->getKey(),
            'approved_at' => now(),
        ])->save();

        $this->logger->log('refund.approved', $refund, "Refund {$refund->refund_code} disetujui.", [], $actor);

        return $refund->refresh();
    }

    public function reject(Refund $refund, User $actor, string $note): Refund
    {
        if (! $refund->status->isOpen()) {
            throw new RuntimeException('Refund ini sudah final.');
        }

        $refund->forceFill([
            'status' => RefundStatus::Rejected,
            'processed_by' => $actor->getKey(),
            'processed_at' => now(),
            'notes' => $note,
        ])->save();

        $this->logger->log('refund.rejected', $refund, "Refund {$refund->refund_code} ditolak.", ['note' => $note], $actor);

        return $refund->refresh();
    }

    /**
     * Proses refund. Bila metode/attempt mendukung refund API gateway, jalankan otomatis;
     * jika tidak, tandai manual_required agar admin mengembalikan dana secara manual.
     */
    public function process(Refund $refund, User $actor): Refund
    {
        if ($refund->status === RefundStatus::Succeeded) {
            return $refund; // idempotent
        }

        if (! in_array($refund->status, [RefundStatus::Approved, RefundStatus::ManualRequired, RefundStatus::Failed], true)) {
            throw new RuntimeException('Refund harus disetujui sebelum diproses.');
        }

        $payment = $refund->payment;
        $attempt = $refund->attempt;

        $canGateway = $payment->method === PaymentMethod::Midtrans
            && $attempt !== null
            && $attempt->isSuccessful()
            && $refund->refund_method === RefundMethod::Gateway;

        if (! $canGateway) {
            $refund->forceFill([
                'status' => RefundStatus::ManualRequired,
                'processed_by' => $actor->getKey(),
            ])->save();

            $this->logger->log('refund.manual_required', $refund, "Refund {$refund->refund_code} perlu diproses manual.", [], $actor);

            return $refund->refresh();
        }

        $refund->forceFill(['status' => RefundStatus::Processing, 'processed_by' => $actor->getKey()])->save();

        try {
            $result = $this->gateway->refund($attempt, (int) $refund->refund_amount, $refund->refund_code);

            $refund->forceFill([
                'status' => RefundStatus::Succeeded,
                'provider_refund_id' => $result['refund_key'] ?? $refund->refund_code,
                'provider_reference' => isset($result['status_code']) ? 'status:'.$result['status_code'] : null,
                'processed_at' => now(),
            ])->save();

            $this->markPaymentRefunded($payment, (int) $refund->refund_amount);
            $this->logger->log('refund.succeeded', $refund, "Refund gateway {$refund->refund_code} berhasil.", ['amount' => $refund->refund_amount], $actor);
        } catch (Throwable $exception) {
            $refund->forceFill([
                'status' => RefundStatus::Failed,
                'notes' => mb_substr($exception->getMessage(), 0, 255),
                'processed_at' => now(),
            ])->save();

            $this->logger->log('refund.failed', $refund, "Refund gateway {$refund->refund_code} gagal.", ['error' => $exception->getMessage()], $actor);

            throw $exception;
        }

        return $refund->refresh();
    }

    /** Selesaikan refund manual setelah dana benar-benar dikembalikan. */
    public function completeManual(Refund $refund, User $actor, string $evidencePath, ?string $note = null): Refund
    {
        if ($refund->status === RefundStatus::Succeeded) {
            return $refund; // idempotent
        }

        if (! in_array($refund->status, [RefundStatus::ManualRequired, RefundStatus::Approved, RefundStatus::Processing], true)) {
            throw new RuntimeException('Refund ini tidak berada pada tahap yang dapat diselesaikan manual.');
        }

        $refund->forceFill([
            'status' => RefundStatus::Succeeded,
            'evidence_path' => $evidencePath,
            'processed_by' => $actor->getKey(),
            'processed_at' => now(),
            'notes' => $note ?? $refund->notes,
        ])->save();

        $this->markPaymentRefunded($refund->payment, (int) $refund->refund_amount);

        $this->logger->log('refund.completed_manual', $refund, "Refund manual {$refund->refund_code} diselesaikan.", ['amount' => $refund->refund_amount], $actor);

        return $refund->refresh();
    }

    private function markPaymentRefunded(Payment $payment, int $refundedAmount): void
    {
        $total = $payment->amountRupiah();
        $already = (int) $payment->refunds()->where('status', RefundStatus::Succeeded->value)->sum('refund_amount');

        $payment->forceFill([
            'status' => $already >= $total ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded,
        ])->save();
    }

    private function settledPaymentFor(RentalOrder $order): Payment
    {
        $payment = $order->payments()
            ->whereIn('status', [PaymentStatus::Verified->value, PaymentStatus::PartiallyRefunded->value])
            ->latest()
            ->first();

        if ($payment === null) {
            throw new RuntimeException('Tidak ada pembayaran terverifikasi yang dapat direfund.');
        }

        return $payment;
    }

    private function code(): string
    {
        return 'RFD-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }
}
