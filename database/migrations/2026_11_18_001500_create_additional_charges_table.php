<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('additional_charges', function (Blueprint $table) {
            $table->id();
            $table->string('charge_code', 40)->unique();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->index();
            $table->string('reason', 255);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('deposit_offset_amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount')->default(0);
            $table->string('evidence_path')->nullable();
            $table->string('status', 32)->default('pending_approval')->index();
            $table->string('payment_status', 32)->default('unpaid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_charges');
    }
};
