<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menyimpan jam keberangkatan (waktu berkumpul) pada paket trip.
 *
 * Kolom aditif dan nullable sehingga paket lama tetap dapat dibuka tanpa
 * backfill. Jam disimpan sebagai `H:i` (5 karakter) karena bukan tanggal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trip_packages', 'departure_time')) {
            return;
        }

        Schema::table('trip_packages', function (Blueprint $table): void {
            $table->string('departure_time', 5)->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('trip_packages', 'departure_time')) {
            return;
        }

        Schema::table('trip_packages', function (Blueprint $table): void {
            $table->dropColumn('departure_time');
        });
    }
};
