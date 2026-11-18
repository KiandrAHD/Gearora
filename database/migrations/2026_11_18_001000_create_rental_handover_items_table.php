<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_handover_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_asset_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_asset_id')->constrained()->restrictOnDelete();
            $table->string('asset_code_snapshot', 64);
            $table->json('accessories_checklist')->nullable();
            $table->text('condition_notes')->nullable();
            $table->boolean('customer_acknowledged')->default(false);
            $table->timestamps();

            $table->unique('rental_asset_assignment_id', 'handover_item_assignment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_handover_items');
    }
};
