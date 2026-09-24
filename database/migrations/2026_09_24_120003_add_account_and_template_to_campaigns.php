<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('whatsapp_account_id')->nullable()->after('id')->constrained('whatsapp_accounts')->nullOnDelete();
            $table->foreignId('campaign_template_id')->nullable()->after('whatsapp_account_id')->constrained('campaign_templates')->nullOnDelete();
            $table->json('column_mapping')->nullable()->after('description');     // Excel col -> variable name
            $table->string('country_code', 5)->default('91')->after('column_mapping');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_account_id']);
            $table->dropForeign(['campaign_template_id']);
            $table->dropColumn(['whatsapp_account_id', 'campaign_template_id', 'column_mapping', 'country_code']);
        });
    }
};
