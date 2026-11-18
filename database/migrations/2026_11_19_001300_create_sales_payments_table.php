<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('method', 24);
            $table->unsignedBigInteger('amount')->default(0);
            // PaymentStatus::value
            $table->string('status', 32)->default('unpaid')->index();
            $table->string('proof_path')->nullable();
            $table->string('reference', 160)->nullable();
            $table->text('customer_note')->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('proof_submitted_at')->nullable();
            $table->timestamps();

            $table->index(['sales_order_id', 'status'], 'sales_payments_order_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_payments');
    }
};
