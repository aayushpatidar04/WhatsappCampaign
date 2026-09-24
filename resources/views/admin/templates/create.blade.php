<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Template</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-teal-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold"><i class="fas fa-plus mr-2"></i>New Template</h1>
                <a href="{{ route('admin.templates.index') }}" class="hover:bg-teal-700 px-4 py-2 rounded-lg transition">Back</a>
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
            <form action="{{ route('admin.templates.store') }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Account <span class="text-red-500">*</span></label>
                    <select name="whatsapp_account_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">-- Select --</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('whatsapp_account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-sm" placeholder="e.g., diwali_greeting">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Language Code</label>
                        <input type="text" name="language_code" value="{{ old('language_code', 'en_IN') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Header Type</label>
                        <select name="header_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="none" {{ old('header_type', 'none') == 'none' ? 'selected' : '' }}>None</option>
                            <option value="text" {{ old('header_type') == 'text' ? 'selected' : '' }}>Text</option>
                            <option value="image" {{ old('header_type') == 'image' ? 'selected' : '' }}>Image</option>
                            <option value="video" {{ old('header_type') == 'video' ? 'selected' : '' }}>Video</option>
                            <option value="document" {{ old('header_type') == 'document' ? 'selected' : '' }}>Document</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Header Text (for text header)</label>
                        <input type="text" name="header_text" value="{{ old('header_text') }}" maxlength="60" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="font-semibold text-sm text-blue-800 mb-2"><i class="fas fa-variables mr-1"></i>Body Variables (order matters)</h4>
                    <p class="text-xs text-blue-600 mb-3">Enter variable names in order. These correspond to {{1}}, {{2}}, {{3}} in your Meta template. Example: name, order_id, amount</p>
                    <input type="text" name="body_variables_json" id="bodyVarInput"
                        value="{{ old('body_variables_json', implode(',', old('body_variables', []))) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm"
                        placeholder="name, order_id, amount">
                    <p class="text-xs text-gray-500 mt-1">Comma-separated variable names</p>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="has_document_header" id="hasDoc" value="1" {{ old('has_document_header') ? 'checked' : '' }} class="rounded">
                    <label for="hasDoc" class="text-sm font-medium text-gray-700">Use uploaded PDF as document header (per-user attachment)</label>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-teal-600 text-white py-3 rounded-lg font-semibold hover:bg-teal-700 transition">
                        <i class="fas fa-save mr-1"></i>Save Template
                    </button>
                    <a href="{{ route('admin.templates.index') }}" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
