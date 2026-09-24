<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;

class WhatsappAccountController extends Controller
{
    public function index()
    {
        $accounts = WhatsappAccount::orderByDesc('created_at')->paginate(20);
        return view('admin.accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.accounts.create');
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'phone_number_id'     => 'required|string',
            'business_id'         => 'nullable|string',
            'access_token'        => 'required|string',
            'api_version'         => 'nullable|string|max:20',
            'webhook_verify_token' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $account = WhatsappAccount::create([
            'name'                  => $request->input('name'),
            'phone_number_id'       => $request->input('phone_number_id'),
            'business_id'           => $request->input('business_id'),
            'access_token_encrypted' => \App\Services\EncryptionService::encrypt($request->input('access_token')),
            'api_version'           => $request->input('api_version', 'v25.0'),
            'webhook_verify_token'  => $request->input('webhook_verify_token'),
            'is_active'             => true,
        ]);

        return redirect()->route('admin.accounts.index')->with('success', 'Account created successfully!');
    }

    public function edit(WhatsappAccount $account)
    {
        return view('admin.accounts.edit', compact('account'));
    }

    public function update(Request $request, WhatsappAccount $account)
    {
        $validator = \Validator::make($request->all(), [
            'name'                => 'required|string|max:255',
            'phone_number_id'     => 'required|string',
            'business_id'         => 'nullable|string',
            'access_token'        => 'nullable|string',
            'api_version'         => 'nullable|string|max:20',
            'webhook_verify_token' => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'name'                 => $request->input('name'),
            'phone_number_id'      => $request->input('phone_number_id'),
            'business_id'          => $request->input('business_id'),
            'api_version'          => $request->input('api_version', 'v25.0'),
            'webhook_verify_token' => $request->input('webhook_verify_token'),
            'is_active'            => (bool) $request->input('is_active', true),
        ];

        if ($request->filled('access_token')) {
            $data['access_token_encrypted'] = \App\Services\EncryptionService::encrypt($request->input('access_token'));
        }

        $account->update($data);

        return redirect()->route('admin.accounts.index')->with('success', 'Account updated!');
    }

    public function destroy(WhatsappAccount $account)
    {
        $account->delete();
        return redirect()->route('admin.accounts.index')->with('success', 'Account deleted.');
    }

    /**
     * Return templates for a given account as JSON (for AJAX).
     */
    public function templates(WhatsappAccount $account)
    {
        $templates = $account->templates()->get(['id', 'name', 'language_code', 'header_type', 'body_variables']);
        return response()->json(['templates' => $templates]);
    }
}
