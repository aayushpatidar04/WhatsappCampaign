<?php

namespace App\Http\Controllers;

use App\Models\CampaignTemplate;
use App\Models\WhatsappAccount;
use App\Services\ExcelParserService;
use App\Services\WhatsAppService;
use App\Services\ZipFileExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $campaigns = \App\Models\Campaign::with(['account', 'template', 'messages'])->latest()->paginate(10);
        return view('campaigns.index', compact('campaigns'));
    }

    /**
     * AJAX: parse uploaded Excel and return column names.
     */
    public function parseExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:10240']);
        try {
            $parser = new ExcelParserService();
            $columns = $parser->getColumns($request->file('file')->getRealPath());
            return response()->json(['columns' => $columns]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function create()
    {
        $accounts = WhatsappAccount::where('is_active', true)->get();
        return view('campaigns.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'campaign_template_id' => 'nullable|exists:campaign_templates,id',
            'country_code'        => 'nullable|string|max:5',
            'phone_column'        => 'nullable|required_without:numbers_input|string',
            'numbers_input'       => 'nullable|required_without:excel_file|string',
            'excel_file'          => 'nullable|required_without:numbers_input|file|mimes:xlsx,xls,csv|max:10240',
            'attachment_zip'      => 'nullable|file|mimes:zip|max:51200',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $phoneColumn = $request->input('phone_column');
        $countryCode = $request->input('country_code', '91');
        $countryCode = preg_replace('/[^0-9]/', '', $countryCode);

        // Determine template for default template_name/language_code
        $template = null;
        if ($request->filled('campaign_template_id')) {
            $template = CampaignTemplate::find($request->campaign_template_id);
        }

        // Parse numbers and variables
        $rows = [];
        if ($request->hasFile('excel_file')) {
            $parser = new ExcelParserService();
            try {
                $rows = $parser->parse($request->file('excel_file')->getRealPath());
                $phoneColumn = $phoneColumn ?: $parser->detectPhoneColumn(array_keys($rows[0] ?? []));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Failed to parse Excel file: ' . $e->getMessage())->withInput();
            }
        } elseif ($request->filled('numbers_input')) {
            $lines = array_filter(array_map('trim', preg_split('/[\n\r]+/', $request->input('numbers_input'))));
            foreach ($lines as $line) {
                $rows[] = ['Phone' => $line];
            }
            $phoneColumn = 'Phone';
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'No valid data found.')->withInput();
        }

        // Build column_mapping: Excel column -> variable name mapping
        $columnMapping = [];
        if ($request->hasFile('excel_file')) {
            foreach (array_keys($rows[0] ?? []) as $col) {
                if (strtolower($col) !== strtolower($phoneColumn)) {
                    $columnMapping[$col] = strtolower(trim($col));
                }
            }
        }

        $campaign = \App\Models\Campaign::create([
            'name'                 => $request->input('name'),
            'description'          => $request->input('description'),
            'whatsapp_account_id'  => $request->input('whatsapp_account_id'),
            'campaign_template_id' => $request->input('campaign_template_id'),
            'template_name'        => $template?->name ?? config('whatsapp.template_name'),
            'language_code'        => $template?->language_code ?? config('whatsapp.language_code'),
            'status'               => 'draft',
            'total_numbers'        => count($rows),
            'column_mapping'       => $columnMapping,
            'country_code'         => $countryCode,
        ]);

        $excelPhoneCol = $phoneColumn;
        $normalizedNumbers = [];

        // Handle attachment zip
        $zipFiles = [];
        if ($request->hasFile('attachment_zip')) {
            try {
                $extractor = new ZipFileExtractorService();
                $zipFiles = $extractor->extractAndMap($request->file('attachment_zip')->getRealPath());
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Failed to process zip file: ' . $e->getMessage())->withInput();
            }
        }

        // Create campaign messages from parsed rows
        foreach ($rows as $index => $row) {
            $rawPhone = $row[$excelPhoneCol] ?? '';
            $phoneNumber = ExcelParserService::normalizePhone($rawPhone, $countryCode);

            if (strlen($phoneNumber) < 10) {
                continue; // skip invalid
            }

            $normalizedNumbers[] = $phoneNumber;

            // Build variables map from other columns (excluding phone column)
            $variables = [];
            foreach ($row as $col => $value) {
                if (strtolower($col) !== strtolower($excelPhoneCol) && $value !== null && $value !== '') {
                    $key = strtolower(trim($col));
                    $variables[$key] = $value;
                }
            }

            $message = \App\Models\CampaignMessage::create([
                'campaign_id'    => $campaign->id,
                'phone_number'   => $phoneNumber,
                'status'         => 'pending',
                'variables'      => !empty($variables) ? $variables : null,
            ]);

            // Store matching file for this number
            if (isset($zipFiles[$phoneNumber]) || isset($zipFiles[preg_replace('/[^0-9]/', '', $rawPhone)])) {
                $fileData = $zipFiles[$phoneNumber] ?? $zipFiles[preg_replace('/[^0-9]/', '', $rawPhone)] ?? null;

                if ($fileData) {
                    $storedPath = (new ZipFileExtractorService())
                        ->storeFile($fileData['path'], $campaign->id, $phoneNumber, $fileData['filename']);

                    \App\Models\CampaignFile::create([
                        'campaign_id'        => $campaign->id,
                        'campaign_message_id' => $message->id,
                        'phone_number'       => $phoneNumber,
                        'original_filename'  => $fileData['filename'],
                        'stored_path'        => $storedPath,
                        'mime_type'          => $fileData['mime_type'],
                        'size_bytes'         => $fileData['size_bytes'],
                    ]);

                    $message->update(['has_attachment' => true]);
                }
            }
        }

        $campaign->update(['total_numbers' => count($normalizedNumbers)]);

        // Cleanup temp files
        foreach ($zipFiles as $f) {
            (new ZipFileExtractorService())->cleanup($f['temp_dir']);
        }

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created with ' . count($normalizedNumbers) . ' contacts and ' . count($zipFiles) . ' file attachments. Ready to send!');
    }

    public function show(\App\Models\Campaign $campaign)
    {
        $campaign->load('messages');
        $stats = [
            'pending'          => $campaign->messages()->where('status', 'pending')->count(),
            'sent'             => $campaign->messages()->where('status', 'sent')->count(),
            'delivered'        => $campaign->messages()->where('status', 'delivered')->count(),
            'read'             => $campaign->messages()->where('status', 'read')->count(),
            'failed'           => $campaign->messages()->where('status', 'failed')->count(),
            'not_registered'   => $campaign->notRegisteredMessages()->count(),
            'health_error'     => $campaign->healthErrorMessages()->count(),
            'blocked'          => $campaign->blockedMessages()->count(),
            'with_attachment'  => $campaign->messages()->where('has_attachment', true)->count(),
        ];
        return view('campaigns.show', compact('campaign', 'stats'));
    }

    public function send(\App\Models\Campaign $campaign)
    {
        if (!$campaign->account) {
            return redirect()->back()->with('error', 'No WhatsApp account configured for this campaign.');
        }
        if (!$campaign->template) {
            return redirect()->back()->with('error', 'No template configured for this campaign.');
        }

        if ($campaign->status !== 'draft' && $campaign->status !== 'paused') {
            return redirect()->back()->with('error', 'Campaign is already ' . $campaign->status);
        }

        // Dispatch one queued job per pending message
        foreach ($campaign->pendingMessages()->cursor() as $message) {
            \App\Jobs\SendCampaignMessageJob::dispatch($message);
        }

        $campaign->update(['status' => 'running', 'started_at' => now()]);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign is running! Jobs dispatched to queue.');
    }

    public function analytics(\App\Models\Campaign $campaign)
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

    public function destroy(\App\Models\Campaign $campaign)
    {
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}
