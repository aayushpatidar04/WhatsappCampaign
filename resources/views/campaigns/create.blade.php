<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - WhatsApp Bulk Sender</title>
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
                        <i class="fas fa-list mr-2"></i>All Campaigns
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create New Campaign
            </h2>

            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('campaigns.store') }}" method="POST" enctype="multipart/form-data"
                class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-heading mr-1"></i>Campaign Name
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="e.g., Summer Sale 2026">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-align-left mr-1"></i>Description (Optional)
                    </label>
                    <textarea name="description" rows="2"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                        placeholder="Brief description of this campaign">{{ old('description') }}</textarea>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-blue-800 mb-2">
                        <i class="fas fa-info-circle"></i>
                        <span class="font-semibold">Template Info</span>
                    </div>
                    <p class="text-sm text-blue-700">
                        Using template: <strong>{{ config('whatsapp.template_name') }}</strong> |
                        Language: <strong>{{ config('whatsapp.language_code') }}</strong>
                    </p>
                </div>

                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-users text-green-600 mr-2"></i>Recipient Numbers
                    </h3>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-paste mr-1"></i>Paste Numbers
                            </label>
                            <textarea name="numbers_input" rows="9"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono text-sm"
                                placeholder="Paste numbers here (one per line, or comma-separated)&#10;9425903075&#10;9425952053&#10;...">{{ old('numbers_input') }}</textarea>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-lightbulb mr-1"></i>One number per line, or separate by commas
                            </p>
                        </div>

                        <div class="flex flex-col justify-center">
                            <div
                                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-500 transition">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload numbers.txt
                                </label>
                                <input type="file" name="numbers_file" accept=".txt,.csv"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                <p class="text-xs text-gray-500 mt-2">Accepts .txt or .csv files</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit"
                        class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        Create Campaign
                    </button>
                    <a href="{{ route('campaigns.index') }}"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
