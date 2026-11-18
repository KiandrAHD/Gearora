<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_order_id', 64)->unique();
            $table->string('provider_transaction_id', 128)->nullable()->unique();
            $table->string('provider_payment_type', 48)->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->string('fraud_status', 32)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->string('currency', 8)->default('IDR');
            $table->string('snap_token', 255)->nullable();
            $table->text('redirect_url')->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->unique(['payment_id', 'idempotency_key'], 'attempt_payment_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
