<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RentalOrderStatus;
use App\Models\Payment;
use App\Models\RentalOrder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pembayaran manual MVP: transfer bank (dengan bukti) dan tunai di lokasi.
 *
 * Bukti transfer tidak pernah otomatis melunasi order; status pembayaran berubah
 * hanya setelah verifikasi Owner/Admin.
 */
class PaymentService
{
    public function __construct(
        private readonly RentalOrderService $orders,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Instruksi pembayaran dari konfigurasi/DB (tidak di-hardcode di view).
     *
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

    public function submitProof(
        Payment $payment,
        UploadedFile $file,
        ?string $reference = null,
        ?string $note = null,
    ): Payment {
        if ($payment->method !== PaymentMethod::Transfer) {
            throw new RuntimeException('Bukti pembayaran hanya berlaku untuk metode transfer.');
        }

        if (in_array($payment->status, [PaymentStatus::Verified, PaymentStatus::Refunded], true)) {
            throw new RuntimeException('Pembayaran ini sudah final dan tidak dapat diubah.');
        }

        $disk = config('gearora.payment.proof_disk');
        $directory = 'payment-proofs/'.$payment->rental_order_id;
        $filename = Str::random(40).'.'.strtolower($file->getClientOriginalExtension() ?: $file->extension());

        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

        return DB::transaction(function () use ($payment, $path, $reference, $note, $disk) {
            $previous = $payment->proof_path;

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

            if ($previous !== null && $previous !== $path) {
                Storage::disk($disk)->delete($previous);
            }

            $order = $payment->order;

            if ($order->order_status === RentalOrderStatus::PendingPayment) {
                $this->orders->transition($order, RentalOrderStatus::PaymentReview, auth()->user());
            }

            $this->logger->log(
                'payment.proof_submitted',
                $payment,
                "Bukti pembayaran order {$order->order_code} diunggah pelanggan.",
                ['payment_id' => $payment->getKey(), 'order_code' => $order->order_code],
            );

            return $payment->refresh();
        });
    }

    public function verify(Payment $payment, User $actor, ?string $note = null): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $note) {
            $payment->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
                'verification_note' => $note,
            ])->save();

            $order = $payment->order;

            if ($order->order_status->canTransitionTo(RentalOrderStatus::Confirmed)) {
                $this->orders->transition($order, RentalOrderStatus::Confirmed, $actor, 'Pembayaran terverifikasi.');
            }

            $this->logger->log(
                'payment.verified',
                $payment,
                "Pembayaran order {$order->order_code} diverifikasi.",
                ['payment_id' => $payment->getKey(), 'amount' => (string) $payment->amount, 'note' => $note],
                $actor,
            );

            return $payment->refresh();
        });
    }

    public function reject(Payment $payment, User $actor, string $note): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $note) {
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
     * Catat penerimaan tunai oleh Admin/Owner saat uang benar-benar diterima.
     */
    public function recordCash(Payment $payment, User $actor, ?string $note = null): Payment
    {
        if ($payment->method !== PaymentMethod::Cash) {
            throw new RuntimeException('Pencatatan tunai hanya berlaku untuk metode tunai.');
        }

        return $this->verify($payment, $actor, $note ?? 'Pembayaran tunai diterima di lokasi.');
    }

    public function ensurePaymentFor(RentalOrder $order, PaymentMethod $method): Payment
    {
        return $order->payments()->firstOrCreate(
            ['method' => $method],
            ['amount' => $order->total, 'status' => PaymentStatus::Unpaid],
        );
    }
}
