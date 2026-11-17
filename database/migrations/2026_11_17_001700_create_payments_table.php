<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->enum('method', ['transfer', 'cash']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('status', ['unpaid', 'waiting_verification', 'verified', 'rejected', 'refunded'])
                ->default('unpaid')->index();
            $table->string('proof_path')->nullable();
            $table->string('reference', 160)->nullable();
            $table->text('customer_note')->nullable();
            $table->text('verification_note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('proof_submitted_at')->nullable();
            $table->timestamps();

            $table->index(['rental_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
