<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wa_message_id')->unique(); // wamid.HBg…
            $table->string('from_phone');              // 918279404381
            $table->string('to_phone');                // 919993048315 (your business number)
            $table->string('from_name')->nullable();   // "HN"
            $table->string('type');                    // text | image | video …
            $table->longText('body')->nullable();      // text body or caption
            $table->text('media_url')->nullable();     // lookaside url
            $table->string('media_mime_type')->nullable();
            $table->json('raw_payload');               // full message JSON
            $table->timestamp('wa_timestamp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_incoming_messages');
    }
};