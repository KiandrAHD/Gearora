<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 48)->unique();
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->index();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('balance_after')->default(0);
            $table->string('reason', 255)->nullable();
            $table->string('evidence_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['deposit_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_transactions');
    }
};
