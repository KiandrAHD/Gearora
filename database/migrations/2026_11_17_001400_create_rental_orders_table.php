<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 32)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            // Batas blokir unit: max(end_date, start_date + 1 hari) agar rental same-day
            // tetap menempati satu hari dan tidak bisa di-double-book (lihat ADR_RENTAL_INTERVAL).
            $table->date('blocking_end_date')->index();
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('deposit_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->enum('order_status', [
                'pending_payment', 'payment_review', 'confirmed', 'ready_for_pickup',
                'picked_up', 'returned', 'completed', 'cancelled',
            ])->default('pending_payment')->index();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'order_status']);
            $table->index(['start_date', 'blocking_end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_orders');
    }
};
