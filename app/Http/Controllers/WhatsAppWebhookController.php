<?php

namespace App\Http\Controllers;

use App\Models\CampaignMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('whatsapp.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('Webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $data = $request->all();

        Log::info('WhatsApp webhook received', ['payload' => $data]);

        $entries = $data['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];

                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->processStatusUpdate($status);
                    }
                }

                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->processIncomingMessage($message);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function processStatusUpdate(array $status): void
    {
        $whatsappId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;
        $timestamp = $status['timestamp'] ?? null;
        $error = $status['errors'][0] ?? null;

        if (!$whatsappId) {
            return;
        }

        $message = CampaignMessage::where('whatsapp_message_id', $whatsappId)->first();

        if (!$message) {
            Log::warning('Webhook: Message not found', ['whatsapp_id' => $whatsappId]);
            return;
        }

        $updateData = [];
        $campaignUpdate = [];

        switch ($statusType) {
            case 'sent':
                $updateData = ['status' => 'sent', 'sent_at' => $this->timestampToDateTime($timestamp)];
                break;

            case 'delivered':
                $updateData = ['status' => 'delivered', 'delivered_at' => $this->timestampToDateTime($timestamp)];
                $campaignUpdate = ['delivered_count' => $message->campaign->delivered_count + 1];
                break;

            case 'read':
                $updateData = ['status' => 'read', 'read_at' => $this->timestampToDateTime($timestamp)];
                $campaignUpdate = ['read_count' => $message->campaign->read_count + 1];
                break;

            case 'failed':
                $errorCode = $error['code'] ?? 'UNKNOWN';
                $errorTitle = $error['title'] ?? 'Unknown error';
                $errorMessage = $error['message'] ?? $errorTitle;
                $failureReason = $this->mapWebhookError($errorCode, $errorMessage);

                $updateData = [
                    'status' => 'failed',
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage,
                    'failure_reason' => $failureReason,
                    'failed_at' => $this->timestampToDateTime($timestamp),
                ];
                $campaignUpdate = ['failed_count' => $message->campaign->failed_count + 1];
                break;
        }

        if (!empty($updateData)) {
            $message->update($updateData);
        }

        if (!empty($campaignUpdate)) {
            $message->campaign->update($campaignUpdate);
        }

        Log::info('Webhook status processed', [
            'whatsapp_id' => $whatsappId,
            'status' => $statusType,
            'phone' => $recipientId,
        ]);
    }

    protected function processIncomingMessage(array $message): void
    {
        $from = $message['from'] ?? null;
        $type = $message['type'] ?? null;

        Log::info('Incoming message received', [
            'from' => $from,
            'type' => $type,
        ]);
    }

    protected function timestampToDateTime(?string $timestamp): ?string
    {
        if (!$timestamp) {
            return now()->toDateTimeString();
        }
        return date('Y-m-d H:i:s', (int) $timestamp);
    }

    protected function mapWebhookError(string $code, string $message): string
    {
        $msg = strtolower($message);

        if (str_contains($msg, 'not registered') || str_contains($msg, 'invalid') || $code === '131026') {
            return 'NOT_REGISTERED';
        }
        if (str_contains($msg, 'health') || str_contains($msg, 'display name') || str_contains($msg, 'quality')) {
            return 'HEALTH_ERROR';
        }
        if (str_contains($msg, 'blocked') || str_contains($msg, 'rejected') || str_contains($msg, 'spam')) {
            return 'BLOCKED';
        }
        if (str_contains($msg, 'rate') || str_contains($msg, 'too many') || $code === '131048') {
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