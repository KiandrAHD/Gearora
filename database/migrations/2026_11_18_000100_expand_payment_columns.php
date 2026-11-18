<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perluas kolom status/metode menjadi string agar kompatibel MySQL dan SQLite,
 * serta mendukung metode pembayaran gateway dan status baru Phase 5.
 * (Lihat ADR-5.3.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method', 32)->default('transfer')->change();
            $table->string('status', 32)->default('unpaid')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_amount')->nullable()->after('amount');
            $table->unsignedBigInteger('deposit_amount')->nullable()->after('rental_amount');
            $table->timestamp('settled_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['rental_amount', 'deposit_amount', 'settled_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['transfer', 'cash'])->change();
            $table->enum('status', ['unpaid', 'waiting_verification', 'verified', 'rejected', 'refunded'])
                ->default('unpaid')->change();
        });
    }
};
