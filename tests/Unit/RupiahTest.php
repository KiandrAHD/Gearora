<?php

namespace Tests\Unit;

use App\Support\Rupiah;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    public function test_zero_and_null_are_formatted(): void
    {
        $this->assertSame('Rp 0', Rupiah::format(0));
        $this->assertSame('Rp 0', Rupiah::format(null));
        $this->assertSame('Rp 0', Rupiah::format(''));
    }

    public function test_integer_amount_uses_indonesian_thousands_separator(): void
    {
        $this->assertSame('Rp 25.000', Rupiah::format(25000));
        $this->assertSame('Rp 1.250.000', Rupiah::format(1250000));
    }

    public function test_negative_amount_places_sign_before_prefix(): void
    {
        $this->assertSame('-Rp 50.000', Rupiah::format(-50000));
    }

    public function test_decimal_amount_uses_comma_separator(): void
    {
        $this->assertSame('Rp 1.234,56', Rupiah::format(1234.56, 2));
        $this->assertSame('Rp 1.250.000,50', Rupiah::format('1250000.50', 2));
    }

    public function test_preformatted_indonesian_string_is_parsed(): void
    {
        $this->assertSame('Rp 1.250.000', Rupiah::format('1.250.000'));
        $this->assertSame('Rp 1.250.000,75', Rupiah::format('1.250.000,75', 2));
    }

    public function test_prefix_can_be_disabled(): void
    {
        $this->assertSame('1.250.000', Rupiah::format(1250000, withPrefix: false));
    }

    public function test_global_helper_is_available(): void
    {
        $this->assertSame('Rp 15.000', rupiah(15000));
        $this->assertSame('Rp 15.000,25', rupiah('15000.25', 2));
    }

    public function test_negative_decimal_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Rupiah::format(1000, -1);
    }
}
