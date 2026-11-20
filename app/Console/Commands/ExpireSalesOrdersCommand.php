<?php

namespace App\Console\Commands;

use App\Services\SalesOrderExpiryService;
use Illuminate\Console\Command;

class ExpireSalesOrdersCommand extends Command
{
    protected $signature = 'gearora:expire-sales-orders {--limit=100 : Maksimum order per eksekusi}';

    protected $description = 'Kedaluwarsakan sales order dan lepaskan reservasi stok yang sudah habis waktu.';

    public function handle(SalesOrderExpiryService $expiry): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $count = $expiry->expireStale($limit);

        $this->info("Sales order kedaluwarsa: {$count}.");

        return self::SUCCESS;
    }
}
