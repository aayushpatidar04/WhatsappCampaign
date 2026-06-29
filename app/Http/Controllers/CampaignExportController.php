<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignExportController extends Controller
{
    public function exportCsv(Campaign $campaign)
    {
        $campaign->load('messages');

        $filename = 'campaign_' . $campaign->id . '_' . $campaign->name . '_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $response = new StreamedResponse(function () use ($campaign) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Campaign Summary Header
            fputcsv($handle, ['CAMPAIGN ANALYTICS REPORT']);
            fputcsv($handle, []);
            fputcsv($handle, ['Campaign Name', $campaign->name]);
            fputcsv($handle, ['Description', $campaign->description ?? 'N/A']);
            fputcsv($handle, ['Template', $campaign->template_name]);
            fputcsv($handle, ['Language', $campaign->language_code]);
            fputcsv($handle, ['Status', ucfirst($campaign->status)]);
            fputcsv($handle, ['Created At', $campaign->created_at->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['Started At', $campaign->started_at ? $campaign->started_at->format('Y-m-d H:i:s') : 'N/A']);
            fputcsv($handle, ['Completed At', $campaign->completed_at ? $campaign->completed_at->format('Y-m-d H:i:s') : 'N/A']);
            fputcsv($handle, []);

            // Stats Summary
            fputcsv($handle, ['SUMMARY STATISTICS']);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Count', 'Percentage']);
            $total = $campaign->total_numbers;
            fputcsv($handle, ['Total Numbers', $total, '100%']);
            fputcsv($handle, ['Sent', $campaign->sent_count, $total > 0 ? round(($campaign->sent_count / $total) * 100, 2) . '%' : '0%']);
            fputcsv($handle, ['Delivered', $campaign->delivered_count, $total > 0 ? round(($campaign->delivered_count / $total) * 100, 2) . '%' : '0%']);
            fputcsv($handle, ['Read', $campaign->read_count, $total > 0 ? round(($campaign->read_count / $total) * 100, 2) . '%' : '0%']);
            fputcsv($handle, ['Failed', $campaign->failed_count, $total > 0 ? round(($campaign->failed_count / $total) * 100, 2) . '%' : '0%']);
            fputcsv($handle, ['Pending', $campaign->pendingMessages()->count(), $total > 0 ? round(($campaign->pendingMessages()->count() / $total) * 100, 2) . '%' : '0%']);
            fputcsv($handle, []);

            // Failure Breakdown
            $failureBreakdown = $campaign->messages()
                ->where('status', 'failed')
                ->selectRaw('failure_reason, COUNT(*) as count')
                ->groupBy('failure_reason')
                ->pluck('count', 'failure_reason')
                ->toArray();

            if (!empty($failureBreakdown)) {
                fputcsv($handle, ['FAILURE BREAKDOWN']);
                fputcsv($handle, []);
                fputcsv($handle, ['Failure Reason', 'Count', 'Percentage of Failures']);
                foreach ($failureBreakdown as $reason => $count) {
                    fputcsv($handle, [
                        $reason,
                        $count,
                        $campaign->failed_count > 0 ? round(($count / $campaign->failed_count) * 100, 2) . '%' : '0%'
                    ]);
                }
                fputcsv($handle, []);
            }

            // Delivery Rates
            fputcsv($handle, ['DELIVERY RATES']);
            fputcsv($handle, []);
            fputcsv($handle, ['Rate Type', 'Percentage']);
            fputcsv($handle, [
                'Delivery Rate (Delivered / Sent)',
                $campaign->sent_count > 0 ? round(($campaign->delivered_count / $campaign->sent_count) * 100, 2) . '%' : 'N/A'
            ]);
            fputcsv($handle, [
                'Read Rate (Read / Delivered)',
                $campaign->delivered_count > 0 ? round(($campaign->read_count / $campaign->delivered_count) * 100, 2) . '%' : 'N/A'
            ]);
            fputcsv($handle, [
                'Overall Success Rate (Delivered / Total)',
                $total > 0 ? round(($campaign->delivered_count / $total) * 100, 2) . '%' : 'N/A'
            ]);
            fputcsv($handle, []);

            // Individual Messages Detail
            fputcsv($handle, ['INDIVIDUAL MESSAGE DETAILS']);
            fputcsv($handle, []);
            fputcsv($handle, [
                'Phone Number',
                'Status',
                'WhatsApp Message ID',
                'Error Message',
                'Error Code',
                'Failure Reason',
                'Sent At',
                'Delivered At',
                'Read At',
                'Failed At'
            ]);

            foreach ($campaign->messages as $message) {
                fputcsv($handle, [
                    $message->phone_number,
                    ucfirst($message->status),
                    $message->whatsapp_message_id ?? 'N/A',
                    $message->error_message ?? 'N/A',
                    $message->error_code ?? 'N/A',
                    $message->failure_reason ?? 'N/A',
                    $message->sent_at ? $message->sent_at->format('Y-m-d H:i:s') : 'N/A',
                    $message->delivered_at ? $message->delivered_at->format('Y-m-d H:i:s') : 'N/A',
                    $message->read_at ? $message->read_at->format('Y-m-d H:i:s') : 'N/A',
                    $message->failed_at ? $message->failed_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            fclose($handle);
        }, 200, $headers);

        return $response;
    }

    public function exportJson(Campaign $campaign)
    {
        $campaign->load('messages');

        $filename = 'campaign_' . $campaign->id . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $campaign->name) . '_' . now()->format('Y-m-d_H-i-s') . '.json';

        $data = [
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'template_name' => $campaign->template_name,
                'language_code' => $campaign->language_code,
                'status' => $campaign->status,
                'total_numbers' => $campaign->messages()->count(),
                'sent_count' => $campaign->messages()->where('status', 'sent')->count(),
                'delivered_count' => $campaign->messages()->where('status', 'delivered')->count(),
                'read_count' => $campaign->messages()->where('status', 'read')->count(),
                'failed_count' => $campaign->messages()->where('status', 'failed')->count(),
                'created_at' => $campaign->created_at->toDateTimeString(),
                'started_at' => $campaign->started_at?->toDateTimeString(),
                'completed_at' => $campaign->completed_at?->toDateTimeString(),
            ],
            'summary' => [
                'delivery_rate' => round(($campaign->messages()->where('status', 'delivered')->count() / ($campaign->messages()->where('status', 'sent')->count() + $campaign->messages()->where('status', 'delivered')->count() + $campaign->messages()->where('status', 'read')->count())) * 100, 2),
                'read_rate' => round(($campaign->messages()->where('status', 'read')->count() / ($campaign->messages()->where('status', 'delivered')->count() + $campaign->messages()->where('status', 'read')->count())) * 100, 2),
                'success_rate' => round((($campaign->messages()->where('status', 'delivered')->count() + $campaign->messages()->where('status', 'read')->count()) / $campaign->messages()->count()) * 100, 2),
            ],
            'failure_breakdown' => $campaign->messages()
                ->where('status', 'failed')
                ->selectRaw('failure_reason, COUNT(*) as count')
                ->groupBy('failure_reason')
                ->pluck('count', 'failure_reason')
                ->toArray(),
            'messages' => $campaign->messages->map(function ($msg) {
                return [
                    'phone_number' => $msg->phone_number,
                    'status' => $msg->status,
                    'whatsapp_message_id' => $msg->whatsapp_message_id,
                    'error_message' => $msg->error_message,
                    'error_code' => $msg->error_code,
                    'failure_reason' => $msg->failure_reason,
                    'sent_at' => $msg->sent_at?->toDateTimeString(),
                    'delivered_at' => $msg->delivered_at?->toDateTimeString(),
                    'read_at' => $msg->read_at?->toDateTimeString(),
                    'failed_at' => $msg->failed_at?->toDateTimeString(),
                ];
            })->toArray(),
            'exported_at' => now()->toDateTimeString(),
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Type' => 'application/json',
        ]);
    }
}