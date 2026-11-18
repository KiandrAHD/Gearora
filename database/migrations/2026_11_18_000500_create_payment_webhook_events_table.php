<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('provider_order_id', 64)->nullable()->index();
            $table->string('provider_transaction_id', 128)->nullable();
            $table->string('event_hash', 128)->unique();
            $table->string('notification_status', 32)->nullable();
            $table->string('processing_status', 32)->default('received')->index();
            $table->json('payload_safe')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('failure_reason', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
