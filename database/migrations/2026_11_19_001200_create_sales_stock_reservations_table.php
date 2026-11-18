<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();

            $table->unsignedInteger('quantity');
            // reserved | consumed | released | expired
            $table->string('status', 20)->default('reserved');

            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status'], 'sales_reservations_variant_status_index');
            $table->index(['sales_order_id', 'status'], 'sales_reservations_order_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_stock_reservations');
    }
};
