<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 40)->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            // PaymentStatus::value, terpisah dari order_status.
            $table->string('payment_status', 32)->default('unpaid');
            // SalesOrderStatus::value.
            $table->string('order_status', 32)->default('pending_payment')->index();

            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('pickup_ready_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'order_status'], 'sales_orders_customer_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
