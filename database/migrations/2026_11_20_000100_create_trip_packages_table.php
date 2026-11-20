<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->uuid('checkout_key')->unique();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity', 32);
            $table->string('destination', 120);
            $table->string('location', 160)->nullable();
            $table->string('experience_level', 16);
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedSmallInteger('participants');
            $table->string('mode', 8);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->foreignId('rental_order_id')->nullable()->constrained('rental_orders')->nullOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->json('plan_snapshot')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_packages');
    }
};
