<?php

namespace Tests\Unit;

use App\Enums\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_payment_methods_have_customer_facing_labels_and_descriptions(): void
    {
        $this->assertSame('QRIS / e-wallet & transfer otomatis (Midtrans)', PaymentMethod::Midtrans->label());
        $this->assertSame('Transfer bank manual', PaymentMethod::Transfer->label());
        $this->assertSame('Bayar tunai saat pengambilan', PaymentMethod::Cash->label());
        $this->assertStringContainsString('Bukan COD pengiriman', PaymentMethod::Cash->description());
        $this->assertSame(['transfer', 'cash'], PaymentMethod::manualValues());
    }
}
