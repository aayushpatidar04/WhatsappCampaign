<?php

namespace App\Http\Controllers;

use App\Models\CampaignTemplate;
use App\Models\WhatsappAccount;
use Illuminate\Http\Request;

class CampaignTemplateController extends Controller
{
    public function index()
    {
        $templates = CampaignTemplate::with('account')->orderByDesc('created_at')->paginate(20);
        return view('admin.templates.index', compact('templates'));
    }

    public function create()
    {
        $accounts = WhatsappAccount::where('is_active', true)->get();
        return view('admin.templates.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'whatsapp_account_id'  => 'required|exists:whatsapp_accounts,id',
            'name'                 => 'required|string|max:255',
            'language_code'        => 'nullable|string|max:10',
            'header_type'          => 'nullable|in:none,text,image,video,document',
            'header_text'          => 'nullable|string|max:60',
            'body_variables_json'  => 'nullable|string',
            'has_document_header'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bodyVars = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('body_variables_json', '')))));

        CampaignTemplate::create([
            'whatsapp_account_id'  => $request->input('whatsapp_account_id'),
            'name'                 => $request->input('name'),
            'language_code'        => $request->input('language_code', 'en_IN'),
            'header_type'          => $request->input('header_type', 'none'),
            'header_text'          => $request->input('header_text'),
            'body_variables'       => $bodyVars,
            'has_document_header'  => (bool) $request->input('has_document_header', false),
            'button_variables'     => $request->input('button_variables', []),
        ]);

        return redirect()->route('admin.templates.index')->with('success', 'Template created!');
    }

    public function edit(CampaignTemplate $template)
    {
        $accounts = WhatsappAccount::where('is_active', true)->get();
        return view('admin.templates.edit', compact('template', 'accounts'));
    }

    public function update(Request $request, CampaignTemplate $template)
    {
        $validator = \Validator::make($request->all(), [
            'whatsapp_account_id'  => 'required|exists:whatsapp_accounts,id',
            'name'                 => 'required|string|max:255',
            'language_code'        => 'nullable|string|max:10',
            'header_type'          => 'nullable|in:none,text,image,video,document',
            'header_text'          => 'nullable|string|max:60',
            'body_variables_json'  => 'nullable|string',
            'has_document_header'  => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $bodyVars = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('body_variables_json', '')))));

        $template->update([
            'whatsapp_account_id'  => $request->input('whatsapp_account_id'),
            'name'                 => $request->input('name'),
            'language_code'        => $request->input('language_code', 'en_IN'),
            'header_type'          => $request->input('header_type', 'none'),
            'header_text'          => $request->input('header_text'),
            'body_variables'       => $bodyVars,
            'has_document_header'  => (bool) $request->input('has_document_header', false),
            'button_variables'     => $request->input('button_variables', []),
        ]);

        return redirect()->route('admin.templates.index')->with('success', 'Template updated!');
    }

    public function destroy(CampaignTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.templates.index')->with('success', 'Template deleted.');
    }
}
