<?php

namespace App\Console\Commands;

use App\Support\ProductionReadiness;
use Illuminate\Console\Command;

/**
 * Deployment gate: memastikan konfigurasi produksi aman. Keluar dengan kode
 * non-nol bila ada pelanggaran sehingga CI/deploy dapat dihentikan.
 */
class CheckProductionReadinessCommand extends Command
{
    protected $signature = 'gearora:production-check';

    protected $description = 'Memeriksa konfigurasi produksi (debug, session, https, gateway).';

    public function handle(): int
    {
        $violations = ProductionReadiness::violations();

        if ($violations === []) {
            $this->info('Konfigurasi produksi aman.');

            return self::SUCCESS;
        }

        $this->error('Ditemukan '.count($violations).' masalah konfigurasi produksi:');

        foreach ($violations as $violation) {
            $this->line(' - '.$violation);
        }

        return self::FAILURE;
    }
}
