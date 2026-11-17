<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 180);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('brand', 120)->nullable();
            $table->enum('type', ['rental', 'sale', 'both'])->default('rental')->index();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->json('specifications')->nullable();
            $table->boolean('requires_deposit')->default(false);
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->decimal('rental_price_per_day', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->string('thumbnail_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
