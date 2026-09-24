<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_messages', function (Blueprint $table) {
            $table->json('variables')->nullable()->after('phone_number');      // {"name":"Rahul","order":"123"}
            $table->boolean('has_attachment')->default(false)->after('variables');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_messages', function (Blueprint $table) {
            $table->dropColumn(['variables', 'has_attachment']);
        });
    }
};
