<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_code', 64)->unique();
            $table->enum('condition', ['good', 'fair', 'needs_repair'])->default('good');
            $table->enum('status', ['available', 'reserved', 'rented', 'maintenance', 'damaged', 'lost'])
                ->default('available');
            $table->date('purchase_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_assets');
    }
};
