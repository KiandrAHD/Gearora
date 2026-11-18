<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->text('description')->nullable();

            // 'semua' (TripActivity::ANY) berarti aturan berlaku untuk aktivitas apa pun.
            $table->string('activity', 40)->index();

            $table->unsignedSmallInteger('min_duration_days')->default(1);
            $table->unsignedSmallInteger('max_duration_days')->nullable();
            $table->unsignedSmallInteger('min_participants')->default(1);
            $table->unsignedSmallInteger('max_participants')->nullable();

            // Daftar TripSpecialNeed::value yang wajib ada pada input pelanggan.
            $table->json('required_special_needs')->nullable();

            $table->foreignId('product_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // TripServiceMode::value (sewa|beli|semua).
            $table->string('service_mode', 16)->default('semua');

            $table->unsignedSmallInteger('recommended_quantity')->default(1);
            $table->boolean('quantity_per_participant')->default(false);
            $table->unsignedInteger('priority')->default(100);

            // Penjelasan aman dengan token :activity, :participants, :duration, :destination.
            $table->text('reason_template')->nullable();

            $table->boolean('is_active')->default(true)->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['activity', 'is_active', 'priority'], 'trip_rules_activity_active_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_recommendation_rules');
    }
};
