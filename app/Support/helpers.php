<?php

use App\Support\Rupiah;

if (! function_exists('rupiah')) {
    /**
     * Format nilai menjadi Rupiah. Dipakai di Blade dan kode aplikasi.
     */
    function rupiah(int|float|string|null $amount, int $decimals = 0): string
    {
        return Rupiah::format($amount, $decimals);
    }
}
