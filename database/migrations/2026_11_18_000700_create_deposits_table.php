<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('required_amount')->default(0);
            $table->unsignedBigInteger('collected_amount')->default(0);
            $table->unsignedBigInteger('deducted_amount')->default(0);
            $table->unsignedBigInteger('refunded_amount')->default(0);
            $table->unsignedBigInteger('forfeited_amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount')->default(0);
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
