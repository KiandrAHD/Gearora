@props(['name' => 'method', 'methods' => null, 'selected' => null, 'gatewayAvailable' => null, 'label' => 'Metode pembayaran'])

@php
    $availableMethods = $methods ?? \App\Enums\PaymentMethod::cases();
    $selectedValue = old($name, $selected ?? \App\Enums\PaymentMethod::Transfer->value);
    $gatewayAvailable = $gatewayAvailable ?? ((bool) config('gearora.payment.gateway_enabled')
        && filled(config('gearora.midtrans.server_key'))
        && filled(config('gearora.midtrans.client_key')));
    $icons = [
        \App\Enums\PaymentMethod::Midtrans->value => 'QRIS',
        \App\Enums\PaymentMethod::Transfer->value => 'BANK',
        \App\Enums\PaymentMethod::Cash->value => 'TUNAI',
    ];
@endphp

<fieldset class="payment-methods">
    <legend class="payment-methods-legend">{{ $label }}</legend>
    <div class="payment-method-grid">
        @foreach($availableMethods as $method)
            @php
                $disabled = $method->value === \App\Enums\PaymentMethod::Midtrans->value && ! $gatewayAvailable;
            @endphp
            <label class="payment-method @if($disabled) is-disabled @endif">
                <input type="radio" name="{{ $name }}" value="{{ $method->value }}"
                       @checked($selectedValue === $method->value)
                       @disabled($disabled)
                       @if($disabled) aria-describedby="payment-method-{{ $method->value }}-note" @endif>
                <span class="payment-method-badge" aria-hidden="true">{{ $icons[$method->value] ?? 'BAYAR' }}</span>
                <span class="payment-method-copy">
                    <strong>{{ $method->label() }}</strong>
                    <small>{{ $method->description() }}</small>
                    @if($disabled)
                        <em id="payment-method-{{ $method->value }}-note">Gateway pembayaran sedang tidak tersedia. Pilih transfer bank atau tunai.</em>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
</fieldset>
