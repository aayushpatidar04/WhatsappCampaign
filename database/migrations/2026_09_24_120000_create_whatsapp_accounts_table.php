<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // Friendly name e.g. "Arihant Main"
            $table->string('phone_number_id');                   // Meta phone number ID
            $table->string('business_id')->nullable();           // WABA ID (optional)
            $table->text('access_token_encrypted');              // Encrypted token
            $table->string('api_version')->default('v25.0');
            $table->string('webhook_verify_token')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
