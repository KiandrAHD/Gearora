<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampirkan siklus hidup unit fisik pada assignment: reserve → handover → return → release.
 * Pelepasan unit (`released_at`) dilakukan per unit setelah inspeksi, bukan per order.
 * (Lihat ADR-5.2.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_asset_assignments', function (Blueprint $table) {
            $table->string('status', 24)->default('reserved')->after('rental_asset_id');
            $table->timestamp('handed_over_at')->nullable()->after('assigned_at');
            $table->timestamp('returned_at')->nullable()->after('handed_over_at');
            $table->unsignedBigInteger('rental_handover_item_id')->nullable()->after('returned_at');
            $table->unsignedBigInteger('rental_return_item_id')->nullable()->after('rental_handover_item_id');
            $table->unsignedBigInteger('current_inspection_id')->nullable()->after('rental_return_item_id');

            $table->index(['rental_order_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::table('rental_asset_assignments', function (Blueprint $table) {
            $table->dropIndex(['rental_order_id', 'released_at']);
            $table->dropColumn([
                'status',
                'handed_over_at',
                'returned_at',
                'rental_handover_item_id',
                'rental_return_item_id',
                'current_inspection_id',
            ]);
        });
    }
};
