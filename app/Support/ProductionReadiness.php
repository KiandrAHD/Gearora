<?php

namespace App\Support;

/**
 * Validasi konfigurasi produksi GEARORA.
 *
 * Dipakai sebagai deployment gate (command `gearora:production-check`) dan
 * assertion saat booting pada environment production. Nilai rahasia tidak
 * pernah ditampilkan; hanya status checklist.
 */
final class ProductionReadiness
{
    /** @return array<int, string> daftar pelanggaran konfigurasi */
    public static function violations(): array
    {
        $violations = [];

        if ((bool) config('app.debug')) {
            $violations[] = 'APP_DEBUG harus false pada production.';
        }

        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $violations[] = 'APP_URL harus memakai https:// pada production.';
        }

        if (blank(config('app.key'))) {
            $violations[] = 'APP_KEY wajib diisi (php artisan key:generate).';
        }

        if (! (bool) config('session.secure')) {
            $violations[] = 'SESSION_SECURE_COOKIE harus true pada HTTPS.';
        }

        if (! (bool) config('session.http_only')) {
            $violations[] = 'SESSION_HTTP_ONLY harus true.';
        }

        if (! in_array((string) config('session.same_site'), ['lax', 'strict'], true)) {
            $violations[] = 'SESSION_SAME_SITE harus lax atau strict.';
        }

        if ((bool) config('gearora.midtrans.fake')) {
            $violations[] = 'MIDTRANS_FAKE harus false pada production.';
        }

        return $violations;
    }

    public static function isReady(): bool
    {
        return self::violations() === [];
    }
}
