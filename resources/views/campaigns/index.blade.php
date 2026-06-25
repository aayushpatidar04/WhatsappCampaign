<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns - WhatsApp Bulk Sender</title>
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
                <a href="{{ route('campaigns.create') }}" class="bg-green-700 hover:bg-green-800 px-4 py-2 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>New Campaign
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                {{ session('error') }}
            </div>
        @endif
        @if(session('info'))
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded">
                {{ session('info') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($campaigns as $campaign)
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                @if($campaign->status == 'completed') bg-green-100 text-green-800
                                @elseif($campaign->status == 'running') bg-blue-100 text-blue-800
                                @elseif($campaign->status == 'failed') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                <i class="fas fa-circle text-[8px] mr-1"></i>{{ ucfirst($campaign->status) }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $campaign->created_at->format('M d, Y') }}</span>
                        </div>

                        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $campaign->name }}</h3>
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $campaign->description ?? 'No description' }}</p>

                        <div class="grid grid-cols-4 gap-2 mb-4 text-center">
                            <div class="bg-gray-50 rounded-lg p-2">
                                <div class="text-lg font-bold text-gray-800">{{ $campaign->total_numbers }}</div>
                                <div class="text-xs text-gray-500">Total</div>
                            </div>
                            <div class="bg-green-50 rounded-lg p-2">
                                <div class="text-lg font-bold text-green-600">{{ $campaign->delivered_count }}</div>
                                <div class="text-xs text-green-600">Delivered</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-2">
                                <div class="text-lg font-bold text-blue-600">{{ $campaign->read_count }}</div>
                                <div class="text-xs text-blue-600">Read</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-2">
                                <div class="text-lg font-bold text-red-600">{{ $campaign->failed_count }}</div>
                                <div class="text-xs text-red-600">Failed</div>
                            </div>
                        </div>

                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            @php
                                $progress = $campaign->total_numbers > 0 
                                    ? (($campaign->sent_count + $campaign->failed_count) / $campaign->total_numbers) * 100 
                                    : 0;
                            @endphp
                            <div class="bg-green-600 h-2 rounded-full transition-all" style="width: {{ $progress }}%"></div>
                        </div>

                        <div class="flex gap-2">
                            <a href="{{ route('campaigns.show', $campaign) }}" 
                                class="flex-1 text-center py-2 px-4 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-eye mr-1"></i>View
                            </a>
                            @if($campaign->status == 'draft')
                                <form action="{{ route('campaigns.send', $campaign) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" 
                                        class="w-full py-2 px-4 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                                        <i class="fas fa-paper-plane mr-1"></i>Send
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('campaigns.analytics', $campaign) }}" 
                                class="py-2 px-4 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-sm font-medium transition">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-16">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No campaigns yet</h3>
                    <p class="text-gray-500 mb-6">Create your first WhatsApp campaign</p>
                    <a href="{{ route('campaigns.create') }}" 
                        class="inline-block bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i>Create Campaign
                    </a>
                </div>
            @endforelse
        </div>

        <div class="mt-6">
            {{ $campaigns->links() }}
        </div>
    </div>
</body>
</html>