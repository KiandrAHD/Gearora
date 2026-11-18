<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guide_category_id')->nullable()->constrained('guide_categories')->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            // Format konten disimpan agar renderer dapat berkembang tanpa breaking.
            $table->string('content_format', 20)->default('markdown');
            $table->string('cover_image_path')->nullable();
            $table->string('status', 20)->default('draft')->index();

            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();

            $table->string('seo_title', 200)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at'], 'guides_status_published_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_guides');
    }
};
