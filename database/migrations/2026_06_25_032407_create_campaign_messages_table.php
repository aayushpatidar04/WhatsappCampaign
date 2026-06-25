<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('phone_number');
            $table->string('whatsapp_message_id')->nullable();
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
            $table->text('error_message')->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_subcode')->nullable();
            $table->string('failure_reason')->nullable(); // NOT_REGISTERED, BLOCKED, HEALTH_ERROR, etc.
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index('whatsapp_message_id');
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_messages');
    }
};