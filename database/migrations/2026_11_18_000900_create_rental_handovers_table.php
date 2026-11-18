<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_handovers', function (Blueprint $table) {
            $table->id();
            $table->string('handover_code', 40)->unique();
            $table->foreignId('rental_order_id')->constrained()->cascadeOnDelete();
            $table->timestamp('handed_over_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_identity_type', 32)->nullable();
            $table->boolean('identity_checked')->default(false);
            $table->boolean('customer_acknowledged')->default(false);
            $table->string('status', 24)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_handovers');
    }
};
