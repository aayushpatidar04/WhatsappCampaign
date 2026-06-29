<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    protected WhatsAppService $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function index()
    {
        $campaigns = Campaign::latest()->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'numbers_input' => 'required_without:numbers_file|string',
            'numbers_file' => 'required_without:numbers_input|file|mimes:txt,csv|max:1024',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $numbers = [];

        if ($request->hasFile('numbers_file')) {
            $file = $request->file('numbers_file');
            $content = file_get_contents($file->getRealPath());
            $numbers = array_filter(array_map('trim', explode("\n", $content)));
        } elseif ($request->filled('numbers_input')) {
            $numbers = array_filter(array_map('trim', preg_split('/[\n\r\t,]+/', $request->input('numbers_input'))));
        }

        $numbers = array_values(array_unique(array_filter($numbers, function ($num) {
            return !empty($num) && preg_match('/^[0-9\s\-+]+$/', $num);
        })));

        if (empty($numbers)) {
            return redirect()->back()->with('error', 'No valid phone numbers found.');
        }

        $campaign = Campaign::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'template_name' => config('whatsapp.template_name'),
            'language_code' => config('whatsapp.language_code'),
            'status' => 'draft',
            'total_numbers' => count($numbers),
        ]);

        foreach ($numbers as $number) {
            CampaignMessage::create([
                'campaign_id' => $campaign->id,
                'phone_number' => preg_replace('/[^0-9]/', '', $number),
                'status' => 'pending',
            ]);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created with ' . count($numbers) . ' numbers. Ready to send!');
    }

    public function show(Campaign $campaign)
    {
        $campaign->load('messages');

        $stats = [
            'pending' => $campaign->messages()->where('status', 'pending')->count(),
            'sent' => $campaign->messages()->where('status', 'sent')->count(),
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'read' => $campaign->messages()->where('status', 'read')->count(),
            'failed' => $campaign->messages()->where('status', 'failed')->count(),
            'not_registered' => $campaign->notRegisteredMessages()->count(),
            'health_error' => $campaign->healthErrorMessages()->count(),
            'blocked' => $campaign->blockedMessages()->count(),
        ];

        return view('campaigns.show', compact('campaign', 'stats'));
    }

    public function send(Campaign $campaign)
    {
        $configErrors = $this->whatsappService->validateConfig();
        if (!empty($configErrors)) {
            return redirect()->back()->with('error', implode(', ', $configErrors));
        }

        if ($campaign->status !== 'draft' && $campaign->status !== 'paused') {
            return redirect()->back()->with('error', 'Campaign is already ' . $campaign->status);
        }

        $campaign->update(['status' => 'running', 'started_at' => now()]);

        // Dispatch one job per pending message
        foreach ($campaign->pendingMessages()->cursor() as $message) {
            \App\Jobs\SendCampaignMessageJob::dispatch($message);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign completed!');
    }

    public function analytics(Campaign $campaign)
    {
        $campaign->load('messages');

        $statusBreakdown = $campaign->messages()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $failureBreakdown = $campaign->messages()
            ->where('status', 'failed')
            ->selectRaw('failure_reason, COUNT(*) as count')
            ->groupBy('failure_reason')
            ->pluck('count', 'failure_reason')
            ->toArray();

        $hourlyStats = $campaign->messages()
            ->selectRaw('HOUR(sent_at) as hour, status, COUNT(*) as count')
            ->whereNotNull('sent_at')
            ->groupBy('hour', 'status')
            ->get();

        return view('campaigns.analytics', compact('campaign', 'statusBreakdown', 'failureBreakdown', 'hourlyStats'));
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}