<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Accounts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-satellite-dish text-2xl"></i>
                    <h1 class="text-xl font-bold">WhatsApp Accounts</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.templates.index') }}" class="hover:bg-indigo-700 px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-file-alt mr-1"></i>Templates
                    </a>
                    <a href="{{ route('campaigns.index') }}" class="hover:bg-indigo-700 px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-paper-plane mr-1"></i>Campaigns
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="hover:bg-indigo-700 px-4 py-2 rounded-lg transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">All Accounts</h2>
            <a href="{{ route('admin.accounts.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                <i class="fas fa-plus mr-1"></i>Add Account
            </a>
        </div>

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone Number ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Version</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $account->name }}</td>
                            <td class="px-4 py-3 font-mono text-sm">{{ $account->phone_number_id }}</td>
                            <td class="px-4 py-3 text-sm">{{ $account->api_version }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold {{ $account->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 flex gap-2">
                                <a href="{{ route('admin.accounts.edit', $account) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                                <form action="{{ route('admin.accounts.destroy', $account) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Delete this account?');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash mr-1"></i>Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No accounts yet. Add your first WhatsApp account.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
