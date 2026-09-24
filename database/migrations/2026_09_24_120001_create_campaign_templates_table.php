<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->onDelete('cascade');
            $table->string('name');                              // Meta template name
            $table->string('language_code')->default('en_IN');
            $table->enum('header_type', ['none', 'text', 'image', 'video', 'document'])->default('none');
            $table->string('header_text')->nullable();           // For text header
            $table->json('body_variables');                      // ['name','order_id'] order matters for Meta API
            $table->boolean('has_document_header')->default(false);
            $table->json('button_variables')->nullable();        // For dynamic URL buttons
            $table->timestamps();

            $table->unique(['whatsapp_account_id', 'name', 'language_code'], 'unique_template_per_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_templates');
    }
};
