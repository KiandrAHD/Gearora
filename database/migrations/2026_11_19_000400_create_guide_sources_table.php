<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_guide_id')->constrained('equipment_guides')->cascadeOnDelete();
            $table->string('source_title', 240);
            $table->string('publisher', 160)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->date('accessed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('equipment_guide_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_sources');
    }
};
