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

if (! function_exists('media_url')) {
    /**
     * URL aset publik yang disajikan controller, tanpa storage symlink.
     */
    function media_url(?string $path): ?string
    {
        return filled($path) ? route('media.show', ['path' => ltrim($path, '/')]) : null;
    }
}
