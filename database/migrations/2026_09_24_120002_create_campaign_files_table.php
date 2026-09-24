<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('campaign_message_id')->nullable()->constrained('campaign_messages')->onDelete('cascade');
            $table->string('phone_number');                      // Normalized digits only
            $table->string('original_filename');                 // e.g. 919876543210.pdf
            $table->string('stored_path');                       // storage/app/private/campaign-files/{campaign}/{file}
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'phone_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_files');
    }
};
