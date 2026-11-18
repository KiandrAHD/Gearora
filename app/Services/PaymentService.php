<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\RentalOrder;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pembayaran: transfer manual, tunai, dan gateway (settlement via webhook).
 *
 * Satu Payment = satu kewajiban pembayaran. Semua efek samping settlement
 * (deposit, konfirmasi order) terpusat di markSettled() agar idempotent.
 */
class PaymentService
{
    public function __construct(
        private readonly RentalOrderService $orders,
        private readonly DepositService $deposits,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function instructions(): array
    {
        $config = config('gearora.payment.instructions');
        $setting = fn (string $key) => Setting::get($key, $config[$key] ?? null);

        return [
            'bank_name' => $setting('bank_name'),
            'bank_account_number' => $setting('bank_account_number'),
            'bank_account_name' => $setting('bank_account_name'),
            'cash_location' => $setting('cash_location'),
        ];
    }

    public function hasTransferInstruction(): bool
    {
        $instructions = $this->instructions();

        return filled($instructions['bank_name'])
            && filled($instructions['bank_account_number']);
    }

    /** Gateway otomatis hanya tersedia bila diaktifkan dan kredensial server ada. */
    public function gatewayAvailable(): bool
    {
        return (bool) config('gearora.payment.gateway_enabled')
            && filled(config('gearora.midtrans.server_key'))
            && filled(config('gearora.midtrans.client_key'));
    }

    public function ensurePaymentFor(RentalOrder $order, PaymentMethod $method): Payment
    {
        return $order->payments()->firstOrCreate(
            ['method' => $method],
            [
                'amount' => $order->total,
                'rental_amount' => Money::toInt($order->subtotal),
                'deposit_amount' => Money::toInt($order->deposit_total),
                'status' => PaymentStatus::Unpaid,
            ],
        );
    }

    public function submitProof(
        Payment $payment,
        UploadedFile $file,
        ?string $reference = null,
        ?string $note = null,
    ): Payment {
        if ($payment->method !== PaymentMethod::Transfer) {
            throw new RuntimeException('Bukti pembayaran hanya berlaku untuk metode transfer.');
        }

        if ($payment->status->isFinal()) {
            throw new RuntimeException('Pembayaran ini sudah final dan tidak dapat diubah.');
        }

        $disk = config('gearora.payment.proof_disk');
        $directory = 'payment-proofs/'.$payment->rental_order_id;
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = Str::random(40).'.'.$extension;

        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

        return DB::transaction(function () use ($payment, $path, $reference, $note) {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $previous = $payment->proof_path;

            // Simpan riwayat bukti; file lama tidak dihapus agar tetap dapat diaudit.
            PaymentProof::query()->create([
                'payment_id' => $payment->getKey(),
                'path' => $path,
                'reference' => $reference,
                'note' => $note,
                'status' => 'submitted',
                'submitted_by' => auth()->id(),
            ]);

            $payment->forceFill([
                'proof_path' => $path,
                'reference' => $reference ?: $payment->reference,
                'customer_note' => $note,
                'status' => PaymentStatus::WaitingVerification,
                'proof_submitted_at' => now(),
                'verification_note' => null,
                'verified_by' => null,
                'verified_at' => null,
            ])->save();

            $order = $payment->order;
            $order->loadMissing('deposit');

            if ($order->order_status === RentalOrderStatus::PendingPayment) {
                $this->orders->transition($order, RentalOrderStatus::PaymentReview, auth()->user());
            }

            $this->logger->log(
                'payment.proof_submitted',
                $payment,
                "Bukti pembayaran order {$order->order_code} diunggah pelanggan.",
                [
                    'payment_id' => $payment->getKey(),
                    'order_code' => $order->order_code,
                    'previous_proof' => $previous,
                    'proof' => $path,
                ],
            );

            return $payment->refresh();
        });
    }

    /**
     * Verifikasi manual (transfer dengan bukti, atau tunai yang benar diterima).
     */
    public function verify(Payment $payment, User $actor, ?string $note = null): Payment
    {
        if ($payment->method->isGateway()) {
            throw new RuntimeException('Pembayaran gateway diverifikasi otomatis melalui webhook.');
        }

        if ($payment->status === PaymentStatus::Verified) {
            return $payment; // idempotent
        }

        if ($payment->status === PaymentStatus::Refunded || $payment->status === PaymentStatus::PartiallyRefunded) {
            throw new RuntimeException('Pembayaran ini sudah final dan tidak dapat diverifikasi.');
        }

        $order = $payment->order;

        if ($order->order_status === RentalOrderStatus::Cancelled) {
            throw new RuntimeException('Order sudah dibatalkan; pembayaran tidak dapat diverifikasi.');
        }

        if ($payment->method === PaymentMethod::Transfer && blank($payment->proof_path)) {
            throw new RuntimeException('Tidak dapat memverifikasi transfer tanpa bukti pembayaran.');
        }

        if ($payment->method === PaymentMethod::Transfer && $payment->status !== PaymentStatus::WaitingVerification) {
            throw new RuntimeException('Transfer hanya dapat diverifikasi setelah bukti diunggah.');
        }

        if ($this->amountMismatch($payment)) {
            throw new RuntimeException('Nominal pembayaran tidak sesuai dengan total tagihan.');
        }

        return $this->markSettled($payment, $actor, 'manual', $note);
    }

    /** Catat penerimaan tunai saat uang benar-benar diterima Admin. */
    public function recordCash(Payment $payment, User $actor, ?string $note = null): Payment
    {
        if ($payment->method !== PaymentMethod::Cash) {
            throw new RuntimeException('Pencatatan tunai hanya berlaku untuk metode tunai.');
        }

        if ($payment->status === PaymentStatus::Verified) {
            return $payment; // idempotent
        }

        if ($this->amountMismatch($payment)) {
            throw new RuntimeException('Nominal pembayaran tidak sesuai dengan total tagihan.');
        }

        return $this->markSettled($payment, $actor, 'cash', $note ?? 'Pembayaran tunai diterima di lokasi.');
    }

    public function reject(Payment $payment, User $actor, string $note): Payment
    {
        if ($payment->status === PaymentStatus::Verified) {
            throw new RuntimeException('Pembayaran yang sudah terverifikasi tidak dapat ditolak.');
        }

        return DB::transaction(function () use ($payment, $actor, $note) {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'verification_note' => $note,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
            ])->save();

            $order = $payment->order;

            if ($order->order_status === RentalOrderStatus::PaymentReview) {
                $this->orders->transition($order, RentalOrderStatus::PendingPayment, $actor, 'Bukti pembayaran ditolak.');
            }

            $this->logger->log(
                'payment.rejected',
                $payment,
                "Pembayaran order {$order->order_code} ditolak.",
                ['payment_id' => $payment->getKey(), 'note' => $note],
                $actor,
            );

            return $payment->refresh();
        });
    }

    /**
     * Efek terpusat pembayaran yang benar-benar diterima. Idempotent.
     * Dipakai verifikasi manual, pencatatan tunai, dan webhook gateway.
     *
     * @param  array<string, mixed>  $context
     */
    public function markSettled(Payment $payment, ?User $actor, string $source, ?string $note = null, array $context = []): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $source, $note, $context) {
            $payment = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::Verified) {
                return $payment; // idempotent: jangan gandakan efek
            }

            $order = RentalOrder::query()->whereKey($payment->rental_order_id)->lockForUpdate()->firstOrFail();

            $late = $order->order_status === RentalOrderStatus::Cancelled;

            $payment->forceFill([
                'status' => $late ? PaymentStatus::Processing : PaymentStatus::Verified,
                'verified_by' => $actor?->getKey(),
                'verified_at' => now(),
                'settled_at' => now(),
                'verification_note' => $late
                    ? 'Pembayaran diterima setelah order dibatalkan; perlu rekonsiliasi/refund.'
                    : $note,
            ])->save();

            if (! $late) {
                // Alokasikan deposit sebagai liabilitas terpisah (bukan pendapatan).
                $depositAmount = (int) ($payment->deposit_amount ?? Money::toInt($order->deposit_total));

                if ($depositAmount > 0) {
                    $deposit = $this->deposits->ensureForOrder($order);
                    $this->deposits->collect($deposit, $depositAmount, 'Deposit dari pembayaran '.($source), $actor, $context['reference'] ?? null);
                }

                $this->confirmOrderAfterPayment($order, $actor);
            }

            $this->logger->log(
                $late ? 'payment.late_settlement' : 'payment.verified',
                $payment,
                $late
                    ? "Pembayaran order {$order->order_code} masuk setelah order dibatalkan."
                    : "Pembayaran order {$order->order_code} terverifikasi ({$source}).",
                array_merge([
                    'payment_id' => $payment->getKey(),
                    'amount' => (string) $payment->amount,
                    'source' => $source,
                    'note' => $note,
                ], $context),
                $actor,
            );

            return $payment->refresh();
        });
    }

    /** Batalkan order karena pembayaran gagal/kedaluwarsa; lepas alokasi unit. */
    public function cancelForFailure(RentalOrder $order, string $reason): void
    {
        $order->refresh();

        if (! in_array($order->order_status, [RentalOrderStatus::PendingPayment, RentalOrderStatus::PaymentReview], true)) {
            return;
        }

        if (! $order->order_status->canTransitionTo(RentalOrderStatus::Cancelled)) {
            return;
        }

        $this->orders->transition($order, RentalOrderStatus::Cancelled, null, $reason);
    }

    public function amountMismatch(Payment $payment): bool
    {
        $total = Money::toInt($payment->order?->total);

        return $total > 0 && $payment->amountRupiah() !== $total;
    }

    /**
     * Konfirmasi order setelah pembayaran settled, melewati tahap review bila perlu.
     */
    private function confirmOrderAfterPayment(RentalOrder $order, ?User $actor): void
    {
        $order->refresh();

        if ($order->order_status === RentalOrderStatus::PendingPayment) {
            $this->orders->transition($order, RentalOrderStatus::PaymentReview, $actor, 'Pembayaran terverifikasi.');
            $order->refresh();
        }

        if ($order->order_status === RentalOrderStatus::PaymentReview) {
            $this->orders->transition($order, RentalOrderStatus::Confirmed, $actor, 'Pembayaran terverifikasi.');
        }
    }
}
