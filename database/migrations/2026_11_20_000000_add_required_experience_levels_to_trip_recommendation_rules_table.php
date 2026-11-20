<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_recommendation_rules', function (Blueprint $table): void {
            $table->json('required_experience_levels')->nullable()->after('required_special_needs');
        });
    }

    public function down(): void
    {
        Schema::table('trip_recommendation_rules', function (Blueprint $table): void {
            $table->dropColumn('required_experience_levels');
        });
    }
};
