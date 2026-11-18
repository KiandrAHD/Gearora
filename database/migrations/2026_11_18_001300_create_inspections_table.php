<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_asset_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stage', 24)->index();
            $table->string('condition_before', 32)->nullable();
            $table->string('condition_after', 32)->nullable();
            $table->string('completeness_status', 32)->nullable();
            $table->text('damage_description')->nullable();
            $table->json('missing_items')->nullable();
            $table->string('result', 16)->default('pass')->index();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_order_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
