<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $baseUrl;
    protected string $accessToken;
    protected string $phoneNumberId;
    protected string $templateName;
    protected string $languageCode;
    protected int $rateLimitPerMinute;
    protected ?string $headerImageUrl;

    public function __construct()
    {
        $this->baseUrl = "https://graph.facebook.com/" . config('whatsapp.api_version');
        $this->accessToken = config('whatsapp.access_token');
        $this->phoneNumberId = config('whatsapp.phone_number_id');
        $this->templateName = config('whatsapp.template_name');
        $this->languageCode = config('whatsapp.language_code');
        $this->rateLimitPerMinute = config('whatsapp.rate_limit_per_minute', 80);
        $this->headerImageUrl = config('whatsapp.header_image_url');
    }

    public function sendCampaignMessage(CampaignMessage $message): array
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', $message->phone_number);

        if (strlen($phoneNumber) < 10) {
            $this->markFailed($message, 'Invalid phone number length', 'INVALID_NUMBER');
            return ['success' => false, 'error' => 'Invalid phone number length'];
        }

        if (strlen($phoneNumber) === 10) {
            $phoneNumber = '91' . $phoneNumber;
        }

        $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";

        $templatePayload = [
            'name' => $this->templateName,
            'language' => ['code' => $this->languageCode],
        ];

        if ($this->headerImageUrl) {
            $templatePayload['components'] = [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'image',
                            'image' => ['link' => $this->headerImageUrl],
                        ],
                    ],
                ],
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phoneNumber,
            'type' => 'template',
            'template' => $templatePayload,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['messages'][0]['id'])) {
                $whatsappId = $responseData['messages'][0]['id'];
                
                $message->update([
                    'whatsapp_message_id' => $whatsappId,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                $message->campaign->increment('sent_count');

                Log::info('WhatsApp message sent', [
                    'campaign_id' => $message->campaign_id,
                    'phone' => $phoneNumber,
                    'whatsapp_id' => $whatsappId,
                ]);

                return ['success' => true, 'whatsapp_id' => $whatsappId];
            }

            $errorMsg = $responseData['error']['message'] ?? 'Unknown API error';
            $errorCode = $responseData['error']['code'] ?? 'UNKNOWN';
            $errorSubcode = $responseData['error']['error_subcode'] ?? null;
            $failureReason = $this->mapFailureReason($errorMsg, $errorCode);

            $this->markFailed($message, $errorMsg, $errorCode, $errorSubcode, $failureReason);

            Log::error('WhatsApp API error', [
                'campaign_id' => $message->campaign_id,
                'phone' => $phoneNumber,
                'error' => $errorMsg,
                'code' => $errorCode,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'code' => $errorCode,
                'failure_reason' => $failureReason,
            ];

        } catch (\Exception $e) {
            $this->markFailed($message, $e->getMessage(), 'EXCEPTION');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendBulkCampaign(Campaign $campaign, callable $progressCallback = null): array
    {
        $pendingMessages = $campaign->pendingMessages()->get();
        $total = $pendingMessages->count();
        $delaySeconds = 60 / $this->rateLimitPerMinute;

        $campaign->update(['status' => 'running', 'started_at' => now()]);

        foreach ($pendingMessages as $index => $message) {
            $result = $this->sendCampaignMessage($message);

            if ($progressCallback) {
                $progressCallback($index + 1, $total, $result);
            }

            if ($index < $total - 1) {
                usleep((int)($delaySeconds * 1000000));
            }
        }

        $campaign->refresh();
        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [
            'total' => $total,
            'successful' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
        ];
    }

    protected function markFailed(CampaignMessage $message, string $error, string $code, ?string $subcode = null, ?string $failureReason = null): void
    {
        $message->update([
            'status' => 'failed',
            'error_message' => $error,
            'error_code' => $code,
            'error_subcode' => $subcode,
            'failure_reason' => $failureReason,
            'failed_at' => now(),
        ]);

        $message->campaign->increment('failed_count');
    }

    protected function mapFailureReason(string $errorMessage, string $errorCode): ?string
    {
        $message = strtolower($errorMessage);

        if (str_contains($message, 'not registered') || str_contains($message, 'not a valid')) {
            return 'NOT_REGISTERED';
        }
        if (str_contains($message, 'health') || str_contains($message, 'display name')) {
            return 'HEALTH_ERROR';
        }
        if (str_contains($message, 'blocked') || str_contains($message, 'rejected')) {
            return 'BLOCKED';
        }
        if (str_contains($message, 'rate limit') || str_contains($message, 'too many')) {
            return 'RATE_LIMITED';
        }
        if (str_contains($message, 'template') || str_contains($message, 'does not exist')) {
            return 'TEMPLATE_ERROR';
        }
        if (str_contains($message, 'permission') || str_contains($message, 'access')) {
            return 'PERMISSION_ERROR';
        }

        return 'OTHER';
    }

    public function validateConfig(): array
    {
        $errors = [];

        if (empty($this->accessToken) || $this->accessToken === 'your_access_token_here') {
            $errors[] = 'WhatsApp access token is not configured';
        }
        if (empty($this->phoneNumberId)) {
            $errors[] = 'WhatsApp Phone Number ID is not configured';
        }
        if (empty($this->templateName)) {
            $errors[] = 'Template name is not configured';
        }

        return $errors;
    }
}