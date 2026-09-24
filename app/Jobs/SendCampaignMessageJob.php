<?php
namespace App\Jobs;

use App\Models\CampaignMessage;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public CampaignMessage $message;

    public function __construct(CampaignMessage $message)
    {
        $this->message = $message;
    }

    public function handle(WhatsAppService $whatsappService)
    {
        $whatsappService->sendCampaignMessage($this->message);
    }
}
