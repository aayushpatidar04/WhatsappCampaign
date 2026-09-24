<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\CampaignTemplate;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WhatsAppService
{
    /**
     * Send a single campaign message using per-campaign account/template config.
     */
    public function sendCampaignMessage(CampaignMessage $message): array
    {
        $campaign = $message->campaign;
        if (!$campaign->account || !$campaign->template) {
            return $this->markFailed($message, 'Missing account or template config', 'CONFIG_ERROR', null, 'CONFIG_ERROR');
        }

        $account = $campaign->account;
        $template = $campaign->template;

        $phoneNumber = preg_replace('/[^0-9]/', '', $message->phone_number);
        if (strlen($phoneNumber) < 10) {
            return $this->markFailed($message, 'Invalid phone number length', 'INVALID_NUMBER', null, 'INVALID_NUMBER');
        }
        if (strlen($phoneNumber) === 10) {
            $phoneNumber = $campaign->country_code . $phoneNumber;
        }

        // Look up per-recipient file from the campaign_files table first (PDFs), fallback to header attachment
        $attachedFile = null;
        if ($message->has_attachment && $message->file) {
            $attachedFile = $message->file;
        } elseif ($template->has_document_header) {
            // Look for a file matching this phone number in campaign_files
            $attachedFile = $campaign->files()->where('phone_number', $phoneNumber)->first();
        }

        // Build template components
        $components = [];

        // Header component (document/image/video/text)
        if ($template->header_type !== 'none') {
            $headerComponent = ['type' => 'header', 'parameters' => []];

            switch ($template->header_type) {
                case 'text':
                    if (!empty($template->header_text)) {
                        $headerComponent['parameters'][] = ['type' => 'text', 'text' => $template->header_text];
                    }
                    break;

                case 'image':
                case 'video':
                case 'document':
                    if ($attachedFile) {
                        // Upload the file via /media endpoint and use the returned handle
                        $mediaId = $this->uploadMedia($account, $attachedFile);
                        if ($mediaId) {
                            $headerComponent['parameters'][] = [
                                'type' => $template->header_type,
                                $template->header_type => ['id' => $mediaId],
                            ];
                        } else {
                            return $this->markFailed($message, 'Media upload failed', 'MEDIA_UPLOAD_ERROR', null, 'OTHER');
                        }
                    }
                    break;
            }

            if (!empty($headerComponent['parameters'])) {
                $components[] = $headerComponent;
            }
        }

        // Body components (variables in {{1}}, {{2}} placeholders)
        $variables = $message->variables ?? [];
        $templateVars = $template->body_variables ?? [];

        if (!empty($templateVars)) {
            $bodyParameters = [];
            foreach ($templateVars as $index => $varName) {
                $value = $variables[$varName] ?? '';
                $bodyParameters[] = ['type' => 'text', 'text' => (string) $value];
            }
            if (!empty($bodyParameters)) {
                $components[] = [
                    'type'       => 'body',
                    'parameters' => $bodyParameters,
                ];
            }
        }

        // Button components (for dynamic URL/CTA buttons)
        if (!empty($template->button_variables)) {
            foreach ($template->button_variables as $btnIndex => $btnVars) {
                $buttonParameters = [];
                foreach ($btnVars as $varName) {
                    $value = $variables[$varName] ?? '';
                    $buttonParameters[] = ['type' => 'text', 'text' => (string) $value];
                }
                if (!empty($buttonParameters)) {
                    $components[] = [
                        'type'        => 'button',
                        'sub_type'    => 'url',
                        'index'       => (string) $btnIndex,
                        'parameters'  => $buttonParameters,
                    ];
                }
            }
        }

        $templatePayload = [
            'name'     => $template->name,
            'language' => ['code' => $template->language_code],
        ];
        if (!empty($components)) {
            $templatePayload['components'] = $components;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phoneNumber,
            'type'              => 'template',
            'template'          => $templatePayload,
        ];

        $accessToken = EncryptionService::decrypt($account->access_token_encrypted);
        $url = "https://graph.facebook.com/{$account->api_version}/{$account->phone_number_id}/messages";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['messages'][0]['id'])) {
                $whatsappId = $responseData['messages'][0]['id'];

                $message->update([
                    'whatsapp_message_id' => $whatsappId,
                    'status'              => 'sent',
                    'sent_at'             => now(),
                ]);
                $message->campaign->increment('sent_count');

                Log::info('WhatsApp message sent', [
                    'campaign_id' => $campaign->id,
                    'phone'       => $phoneNumber,
                    'whatsapp_id' => $whatsappId,
                ]);

                return ['success' => true, 'whatsapp_id' => $whatsappId];
            }

            $errorMsg    = $responseData['error']['message'] ?? 'Unknown API error';
            $errorCode   = $responseData['error']['code'] ?? 'UNKNOWN';
            $errorSub    = $responseData['error']['error_subcode'] ?? null;
            $failureReason = $this->mapFailureReason($errorMsg, $errorCode);

            return $this->markFailed($message, $errorMsg, $errorCode, $errorSub, $failureReason);

        } catch (\Exception $e) {
            return $this->markFailed($message, $e->getMessage(), 'EXCEPTION', null, 'OTHER');
        }
    }

    /**
     * Upload a campaign file to Meta and return the media id (handle).
     */
    protected function uploadMedia(WhatsappAccount $account, $file): ?string
    {
        $absolutePath = Storage::disk('local')->path($file->stored_path);
        if (!file_exists($absolutePath)) {
            Log::error('Campaign file not found on disk', ['path' => $absolutePath]);
            return null;
        }

        try {
            $accessToken = EncryptionService::decrypt($account->access_token_encrypted);
            $url = "https://graph.facebook.com/{$account->api_version}/{$account->phone_number_id}/media";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->timeout(60)
            ->attach(
                'file',
                file_get_contents($absolutePath),
                $file->original_filename
            )
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'type'              => $file->mime_type,
            ]);

            $data = $response->json();
            if ($response->successful() && isset($data['id'])) {
                return $data['id'];
            }

            Log::error('Media upload failed', ['response' => $data]);
            return null;

        } catch (\Exception $e) {
            Log::error('Media upload exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Send campaign synchronously with rate limiting (used by CLI).
     */
    public function sendBulkCampaign(Campaign $campaign, callable $progressCallback = null): array
    {
        $rateLimit = (int) env('RATE_LIMIT_PER_MINUTE', 30);
        $delaySeconds = 60 / max($rateLimit, 1);

        $campaign->update(['status' => 'running', 'started_at' => now()]);

        $pendingMessages = $campaign->pendingMessages()->cursor();
        $total = 0;
        $index = 0;

        foreach ($pendingMessages as $message) {
            $result = $this->sendCampaignMessage($message);
            $total++;
            if ($progressCallback) {
                $progressCallback($index + 1, $total, $result);
            }
            $index++;
            usleep((int)($delaySeconds * 1000000));
        }

        $campaign->refresh();
        $campaign->update([
            'status'        => 'completed',
            'completed_at'  => now(),
        ]);

        return [
            'total'      => $total,
            'successful' => $campaign->sent_count,
            'failed'     => $campaign->failed_count,
        ];
    }

    protected function markFailed(CampaignMessage $message, string $error, string $code, ?string $subcode, ?string $reason): array
    {
        $message->update([
            'status'         => 'failed',
            'error_message'  => $error,
            'error_code'     => $code,
            'error_subcode'  => $subcode,
            'failure_reason' => $reason,
            'failed_at'      => now(),
        ]);
        $message->campaign->increment('failed_count');

        Log::error('WhatsApp send failed', [
            'campaign_id' => $message->campaign_id,
            'phone'       => $message->phone_number,
            'error'       => $error,
            'code'        => $code,
        ]);

        return [
            'success' => false,
            'error'   => $error,
            'code'    => $code,
        ];
    }

    protected function mapFailureReason(string $errorMessage, string $errorCode): ?string
    {
        $msg = strtolower($errorMessage);
        if (str_contains($msg, 'not registered') || str_contains($msg, 'not a valid') || $errorCode === '131026') {
            return 'NOT_REGISTERED';
        }
        if (str_contains($msg, 'health') || str_contains($msg, 'display name') || str_contains($msg, 'quality')) {
            return 'HEALTH_ERROR';
        }
        if (str_contains($msg, 'blocked') || str_contains($msg, 'rejected') || str_contains($msg, 'spam')) {
            return 'BLOCKED';
        }
        if (str_contains($msg, 'rate') || str_contains($msg, 'too many') || $errorCode === '131048') {
            return 'RATE_LIMITED';
        }
        if (str_contains($msg, 'template') || str_contains($msg, 'does not exist')) {
            return 'TEMPLATE_ERROR';
        }
        if (str_contains($msg, 'permission') || str_contains($msg, 'access') || str_contains($msg, 'unauthorized')) {
            return 'PERMISSION_ERROR';
        }

        return 'OTHER';
    }
}
