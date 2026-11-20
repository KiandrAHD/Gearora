@props(['status'])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = $status instanceof \BackedEnum && method_exists($status, 'label') ? $status->label() : $value;
    $tone = match ($value) {
        'verified', 'completed', 'available', 'active', 'good', 'confirmed' => 'ok',
        'waiting_verification', 'payment_review', 'pending_payment', 'awaiting_payment', 'partially_confirmed', 'reserved', 'fair', 'draft' => 'warn',
        'rejected', 'cancelled', 'damaged', 'lost', 'archived', 'needs_repair' => 'bad',
        default => 'neutral',
    };
@endphp
<span class="status-badge status-{{ $tone }}">{{ $label }}</span>
