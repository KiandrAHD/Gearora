<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->decimal('rental_price_snapshot', 12, 2);
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('duration_days');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_order_items');
    }
};
