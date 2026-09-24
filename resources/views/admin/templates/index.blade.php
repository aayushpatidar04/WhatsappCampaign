<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-teal-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold"><i class="fas fa-file-alt mr-2"></i>Campaign Templates</h1>
                <div class="flex gap-4">
                    <a href="{{ route('admin.accounts.index') }}" class="hover:bg-teal-700 px-4 py-2 rounded-lg transition text-sm">Accounts</a>
                    <a href="{{ route('campaigns.index') }}" class="hover:bg-teal-700 px-4 py-2 rounded-lg transition text-sm">Campaigns</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="hover:bg-teal-700 px-4 py-2 rounded-lg transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">All Templates</h2>
            <a href="{{ route('admin.templates.create') }}" class="bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition">
                <i class="fas fa-plus mr-1"></i>Add Template
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
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Language</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Header</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variables</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($templates as $tpl)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $tpl->name }}</td>
                            <td class="px-4 py-3 text-sm">{{ $tpl->account->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm">{{ $tpl->language_code }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100">{{ $tpl->header_type }}</span></td>
                            <td class="px-4 py-3 text-xs">{{ implode(', ', $tpl->body_variables ?? []) }}</td>
                            <td class="px-4 py-3 flex gap-2">
                                <a href="{{ route('admin.templates.edit', $tpl) }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.templates.destroy', $tpl) }}" method="POST" class="inline" onsubmit="return confirm('Delete?');">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800 text-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No templates yet. Create your first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
