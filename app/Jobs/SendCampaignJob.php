<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Campaign $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function handle(WhatsAppService $whatsAppService)
    {
        $whatsAppService->sendBulkCampaign($this->campaign, function ($current, $total, $result) {
            // Progress handled via DB
        });
    }
}
