<?php

namespace App\Http\Controllers;

use App\Models\CampaignMessage;
use App\Models\WhatsappIncomingMessage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

        Log::info('WhatsApp webhook received', [
            'payload' => json_encode($data, JSON_PRETTY_PRINT)
        ]);

        $entries = $data['entry'] ?? [];
        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];
            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                
                $metadata = $value['metadata'] ?? [];

                $toPhone = $metadata['display_phone_number'] ?? null;

                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->processStatusUpdate($status);
                    }
                }

                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->storeMessage($message, $toPhone, $value['contacts'] ?? []);
                        $this->processIncomingMessage($message);
                    }
                }
            }
        }
        
        // Http::post(
        //     'https://crm.arihantcapital.com/webhooks/meta/whatsapp',
        //     $data
        // );

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
    
    protected function storeMessage(array $message, ?string $toPhone, array $contacts): void
    {
        $waMessageId = $message['id'] ?? null;

        // Skip duplicates
        if (WhatsappIncomingMessage::where('wa_message_id', $waMessageId)->exists()) {
            return;
        }

        $from = $message['from'] ?? null;
        $type = $message['type'] ?? 'unknown';

        // Grab sender name from contacts array
        $fromName = null;
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? null) === $from) {
                $fromName = $contact['profile']['name'] ?? null;
                break;
            }
        }

        // Extract body / media based on type
        $body     = null;
        $mediaUrl = null;
        $mimeType = null;

        switch ($type) {
            case 'text':
                $body = $message['text']['body'] ?? null;
                break;

            case 'image':
                $mediaUrl = $message['image']['url']       ?? null;
                $mimeType = $message['image']['mime_type'] ?? null;
                $body     = $message['image']['caption']   ?? null;
                break;

            case 'video':
                $mediaUrl = $message['video']['url']       ?? null;
                $mimeType = $message['video']['mime_type'] ?? null;
                $body     = $message['video']['caption']   ?? null;
                break;

            case 'document':
                $mediaUrl = $message['document']['url']       ?? null;
                $mimeType = $message['document']['mime_type'] ?? null;
                $body     = $message['document']['caption']   ?? null;
                break;

            case 'audio':
            case 'voice':
                $mediaUrl = $message[$type]['url']       ?? null;
                $mimeType = $message[$type]['mime_type'] ?? null;
                break;

            case 'location':
                $body = json_encode($message['location'] ?? []);
                break;

            case 'button':
                $body = $message['button']['text'] ?? null;
                break;

            case 'interactive':
                $body = $message['interactive']['button_reply']['title']
                     ?? $message['interactive']['list_reply']['title']
                     ?? null;
                break;
        }

        WhatsappIncomingMessage::create([
            'wa_message_id'    => $waMessageId,
            'from_phone'       => $from,
            'to_phone'         => $toPhone,
            'from_name'        => $fromName,
            'type'             => $type,
            'body'             => $body,
            'media_url'        => $mediaUrl,
            'media_mime_type'  => $mimeType,
            'raw_payload'      => $message,
            'wa_timestamp'     => Carbon::createFromTimestamp((int) ($message['timestamp'] ?? now()->timestamp)),
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