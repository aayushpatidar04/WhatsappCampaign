<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register your custom command
Artisan::command('whatsapp:send-campaign {campaign_id}', function ($campaign_id) {
    $this->call(\App\Console\Commands\SendWhatsAppCampaign::class, ['campaign_id' => $campaign_id]);
})->describe('Send WhatsApp campaign from terminal');