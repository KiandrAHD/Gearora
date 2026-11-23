@props(['status'])
@php
    use App\Enums\PaymentStatus;

    $label = $status instanceof PaymentStatus ? $status->documentLabel() : 'Belum Dibayar';

    $tone = match ($status) {
        PaymentStatus::Verified => 'ok',
        PaymentStatus::WaitingVerification, PaymentStatus::Processing => 'warn',
        PaymentStatus::Rejected, PaymentStatus::Expired, PaymentStatus::Failed => 'bad',
        default => 'neutral',
    };
@endphp
<span class="status-badge status-{{ $tone }}">{{ $label }}</span>
