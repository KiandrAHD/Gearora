<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_code', 40)->unique();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('refund_amount');
            $table->unsignedBigInteger('rental_amount')->default(0);
            $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->string('refund_method', 32);
            $table->string('refund_type', 32)->default('combined');
            $table->string('reason', 255);
            $table->string('status', 32)->default('pending_approval')->index();
            $table->string('provider_refund_id', 128)->nullable()->unique();
            $table->string('provider_reference', 128)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('evidence_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
