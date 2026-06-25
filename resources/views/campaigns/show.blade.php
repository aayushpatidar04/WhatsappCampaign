<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $campaign->name }} - Campaign Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen">
    <nav class="bg-green-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fab fa-whatsapp text-2xl"></i>
                    <h1 class="text-xl font-bold">WhatsApp Campaign Manager</h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="{{ route('campaigns.index') }}" class="hover:bg-green-700 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="hover:bg-green-700 px-4 py-2 rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Campaign Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">{{ $campaign->name }}</h2>
                    <p class="text-gray-600 text-sm mb-4">{{ $campaign->description ?? 'No description' }}</p>

                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status</span>
                            <span
                                class="px-2 py-1 rounded text-xs font-semibold
                                @if ($campaign->status == 'completed') bg-green-100 text-green-800
                                @elseif($campaign->status == 'running') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst($campaign->status) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Template</span>
                            <span class="font-medium">{{ $campaign->template_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Language</span>
                            <span class="font-medium">{{ $campaign->language_code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created</span>
                            <span class="font-medium">{{ $campaign->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        @if ($campaign->started_at)
                            <div class="flex justify-between">
                                <span class="text-gray-500">Started</span>
                                <span class="font-medium">{{ $campaign->started_at->format('M d, Y H:i') }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($campaign->status == 'draft')
                        <form action="{{ route('campaigns.send', $campaign) }}" method="POST" class="mt-6">
                            @csrf
                            <button type="submit"
                                class="w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition">
                                <i class="fas fa-paper-plane mr-2"></i>Start Campaign
                            </button>
                        </form>
                    @endif

                    <!-- Export Buttons -->
                    <div class="mt-6 space-y-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Export Analytics
                        </p>
                        <a href="{{ route('campaigns.export.csv', $campaign) }}"
                            class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition text-sm font-medium">
                            <i class="fas fa-file-csv text-green-600"></i>
                            Export as CSV
                        </a>
                        <a href="{{ route('campaigns.export.json', $campaign) }}"
                            class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg transition text-sm font-medium">
                            <i class="fas fa-file-code text-blue-600"></i>
                            Export as JSON
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ $stats['pending'] }}</div>
                        <div class="text-sm text-gray-500">Pending</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600">{{ $stats['sent'] }}</div>
                        <div class="text-sm text-blue-500">Sent</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-green-600">{{ $stats['delivered'] }}</div>
                        <div class="text-sm text-green-500">Delivered</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600">{{ $stats['read'] }}</div>
                        <div class="text-sm text-purple-500">Read</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-red-600">{{ $stats['failed'] }}</div>
                        <div class="text-sm text-red-500">Failed</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-orange-600">{{ $stats['not_registered'] }}</div>
                        <div class="text-sm text-orange-500">Not Registered</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-yellow-600">{{ $stats['health_error'] }}</div>
                        <div class="text-sm text-yellow-500">Health Error</div>
                    </div>
                    <div class="bg-white rounded-xl shadow-md p-4 text-center">
                        <div class="text-3xl font-bold text-pink-600">{{ $stats['blocked'] }}</div>
                        <div class="text-sm text-pink-500">Blocked</div>
                    </div>
                </div>

                <!-- Messages Table -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-800">Messages</h3>
                        <span class="text-sm text-gray-500">{{ $campaign->messages->count() }} total</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">WhatsApp
                                        ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Error
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        Delivered</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($campaign->messages->take(50) as $message)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-mono">{{ $message->phone_number }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="px-2 py-1 rounded text-xs font-semibold
                                                @if ($message->status == 'delivered') bg-green-100 text-green-800
                                                @elseif($message->status == 'read') bg-purple-100 text-purple-800
                                                @elseif($message->status == 'sent') bg-blue-100 text-blue-800
                                                @elseif($message->status == 'failed') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($message->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-mono text-gray-500 truncate max-w-[150px]">
                                            {{ $message->whatsapp_message_id ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-red-600 truncate max-w-[200px]">
                                            {{ $message->error_message ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            {{ $message->sent_at ? $message->sent_at->format('H:i:s') : '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500">
                                            {{ $message->delivered_at ? $message->delivered_at->format('H:i:s') : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                            No messages yet
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($campaign->messages->count() > 50)
                        <div class="px-6 py-3 border-t text-center text-sm text-gray-500">
                            Showing first 50 of {{ $campaign->messages->count() }} messages.
                            <a href="{{ route('campaigns.export.csv', $campaign) }}"
                                class="text-green-600 hover:underline">Download full list</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>

</html>
