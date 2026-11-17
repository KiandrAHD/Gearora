<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_asset_id')->constrained()->restrictOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['rental_asset_id', 'released_at']);
            $table->unique(['rental_order_item_id', 'rental_asset_id'], 'assignment_item_asset_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_asset_assignments');
    }
};
