<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->string('order_status', 32)->default('pending_payment')->change();
        });

        Schema::table('rental_assets', function (Blueprint $table) {
            $table->string('status', 32)->default('available')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rental_orders', function (Blueprint $table) {
            $table->enum('order_status', [
                'pending_payment', 'payment_review', 'confirmed', 'ready_for_pickup',
                'picked_up', 'returned', 'completed', 'cancelled',
            ])->default('pending_payment')->change();
        });

        Schema::table('rental_assets', function (Blueprint $table) {
            $table->enum('status', ['available', 'reserved', 'rented', 'maintenance', 'damaged', 'lost'])
                ->default('available')->change();
        });
    }
};
