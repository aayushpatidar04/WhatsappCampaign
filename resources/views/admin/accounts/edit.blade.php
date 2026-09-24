<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold"><i class="fas fa-edit mr-2"></i>Edit Account</h1>
                <a href="{{ route('admin.accounts.index') }}" class="hover:bg-indigo-700 px-4 py-2 rounded-lg transition">Back</a>
            </div>
        </div>
    </nav>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md p-8">
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <ul class="list-disc list-inside">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
                </div>
            @endif
            <form action="{{ route('admin.accounts.update', $account) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Account Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $account->name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number ID <span class="text-red-500">*</span></label>
                        <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $account->phone_number_id) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business ID</label>
                        <input type="text" name="business_id" value="{{ old('business_id', $account->business_id) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Access Token</label>
                    <input type="text" name="access_token" value="{{ old('access_token') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" placeholder="Leave blank to keep current">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep existing token.</p>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Version</label>
                        <input type="text" name="api_version" value="{{ old('api_version', $account->api_version) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Webhook Verify Token</label>
                        <input type="text" name="webhook_verify_token" value="{{ old('webhook_verify_token', $account->webhook_verify_token) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $account->is_active ? 'checked' : '' }} class="rounded">
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                </div>
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                        <i class="fas fa-save mr-1"></i>Update Account
                    </button>
                    <a href="{{ route('admin.accounts.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
