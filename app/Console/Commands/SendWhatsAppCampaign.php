<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendWhatsAppCampaign extends Command
{
    protected $signature = 'whatsapp:send-campaign
                            {campaign_id : The ID of the campaign to send}
                            {--delay=80 : Rate limit - messages per minute}';

    protected $description = 'Send WhatsApp campaign from terminal';

    public function handle(WhatsAppService $whatsappService): int
    {
        $campaignId = $this->argument('campaign_id');
        $campaign = Campaign::find($campaignId);

        if (!$campaign) {
            $this->error("Campaign #{$campaignId} not found!");
            return 1;
        }

        $this->info("🚀 WhatsApp Campaign: {$campaign->name}");
        $this->info("================================");
        
        $pendingCount = $campaign->pendingMessages()->count();
        $this->info("📋 Pending messages: {$pendingCount}");
        $this->info("📨 Template: {$campaign->template_name}");
        $this->info("🌐 Language: {$campaign->language_code}");
        $this->newLine();

        if ($pendingCount === 0) {
            $this->warn('No pending messages to send.');
            return 0;
        }

        if (!$this->confirm('Do you want to proceed with sending?', true)) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar($pendingCount);
        $progressBar->start();

        $startTime = microtime(true);

        $results = $whatsappService->sendBulkCampaign($campaign, function ($current, $total, $result) use ($progressBar) {
            $progressBar->advance();

            if (!$result['success']) {
                $this->newLine();
                $this->error("❌ Failed: {$result['error']}");
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $campaign->refresh();

        $this->info('✅ Campaign Complete!');
        $this->info('=====================');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Messages', $results['total']],
                ['Successful', $results['successful']],
                ['Failed', $results['failed']],
                ['Delivered', $campaign->delivered_count],
                ['Read', $campaign->read_count],
                ['Duration', "{$duration} seconds"],
            ]
        );

        return $results['failed'] > 0 ? 1 : 0;
    }
}