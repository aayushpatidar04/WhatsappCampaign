<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - WhatsApp Sender</title>
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
                    <a href="{{ route('admin.accounts.index') }}"
                        class="hover:bg-green-700 px-4 py-2 rounded-lg transition text-sm">
                        <i class="fas fa-cog mr-2"></i>Accounts
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="hover:bg-green-700 px-4 py-2 rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-1">
                <i class="fas fa-plus-circle text-green-600 mr-2"></i>Create New Campaign
            </h2>
            <p class="text-gray-500 text-sm mb-6">Select an account, template, upload your Excel sheet with
                names+numbers, and optionally a zip of PDFs.</p>

            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('campaigns.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
                @csrf

                {{-- Step 1: Account + Template --}}
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><i
                            class="fas fa-satellite-dish text-blue-600 mr-2"></i>1. Select WhatsApp Account & Template
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Account <span
                                    class="text-red-500">*</span></label>
                            <select name="whatsapp_account_id" id="accountSelect"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Select Account --</option>
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('whatsapp_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} ({{ $acc->phone_number_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Template</label>
                            <select name="campaign_template_id" id="templateSelect"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Select Template --</option>
                            </select>
                            <p id="templateInfo" class="text-xs text-gray-500 mt-1 hidden"></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Country Code</label>
                        <input type="text" name="country_code" value="{{ old('country_code', '91') }}" maxlength="5"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                {{-- Step 2: Campaign Info --}}
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4"><i
                            class="fas fa-info-circle text-gray-600 mr-2"></i>2. Campaign Info</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                                placeholder="e.g., Diwali Greetings">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Optional">
                        </div>
                    </div>
                </div>

                {{-- Step 3: Excel Upload --}}
                <div class="bg-green-50 rounded-xl p-6 border border-green-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1"><i
                            class="fas fa-file-excel text-green-600 mr-2"></i>3. Upload Excel / CSV</h3>
                    <p class="text-sm text-gray-600 mb-4">Columns should include at least a phone number column. Other
                        columns become template variables (e.g., Name, OrderID).</p>

                    <div class="bg-white border-2 border-dashed border-green-300 rounded-lg p-6 text-center">
                        <i class="fas fa-cloud-upload-alt text-3xl text-green-400 mb-3"></i>
                        <input type="file" name="excel_file" id="excelFileInput" accept=".xlsx,.xls,.csv"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                    </div>

                    <div id="columnMappingSection" class="hidden mt-4 bg-white rounded-lg p-4 border border-green-200">
                        <p class="text-sm font-medium text-gray-700 mb-2"><i class="fas fa-columns mr-1"></i>Map your
                            columns</p>
                        <div class="grid md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number Column</label>
                                <select name="phone_column" id="phoneColumnSelect"
                                    class="w-full px-3 py-2 border border-gray-300 rounded text-sm"></select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Body Variables (will map to
                                    template {{1}}, {{2}}...)</label>
                                <div id="variableCheckboxes" class="flex flex-wrap gap-2 text-sm"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Or paste numbers manually</label>
                        <textarea name="numbers_input" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-sm"
                            placeholder="One number per line">{{ old('numbers_input') }}</textarea>
                    </div>
                </div>

                {{-- Step 4: Attachment Zip --}}
                <div class="bg-yellow-50 rounded-xl p-6 border border-yellow-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1"><i
                            class="fas fa-file-pdf text-red-600 mr-2"></i>4. Per-User Attachment (Optional)</h3>
                    <p class="text-sm text-gray-600 mb-3">Upload a .zip containing files named by phone number: <code
                            class="bg-yellow-100 px-1 rounded">919876543210.pdf</code> or <code
                            class="bg-yellow-100 px-1 rounded">9876543210.pdf</code>. Each file will be sent as a
                        document header to that specific user.</p>
                    <div class="bg-white border-2 border-dashed border-yellow-300 rounded-lg p-4 text-center">
                        <input type="file" name="attachment_zip" accept=".zip"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100">
                    </div>
                </div>

                <div class="flex gap-4 pt-2">
                    <button type="submit"
                        class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition flex items-center justify-center gap-2 text-lg">
                        <i class="fas fa-rocket"></i>Create Campaign
                    </button>
                    <a href="{{ route('campaigns.index') }}"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const excelInput = document.getElementById('excelFileInput');
        const columnSection = document.getElementById('columnMappingSection');
        const phoneSelect = document.getElementById('phoneColumnSelect');
        const variableDiv = document.getElementById('variableCheckboxes');
        const accountSelect = document.getElementById('accountSelect');
        const templateSelect = document.getElementById('templateSelect');
        const templateInfo = document.getElementById('templateInfo');

        excelInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            fetch('{{ route("admin.parse-excel") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.columns) {
                        phoneSelect.innerHTML = '';
                        variableDiv.innerHTML = '';
                        data.columns.forEach(col => {
                            const opt = document.createElement('option');
                            opt.value = col;
                            opt.textContent = col;
                            phoneSelect.appendChild(opt);

                            const label = document.createElement('label');
                            label.className = 'flex items-center gap-1 bg-gray-100 px-2 py-1 rounded cursor-pointer hover:bg-gray-200';
                            label.innerHTML = '<input type="checkbox" name="body_variables[' + col + ']" value="1" class="rounded"> ' + col;
                            variableDiv.appendChild(label);
                        });
                        columnSection.classList.remove('hidden');
                    }
                })
                .catch(() => { });
        });

        accountSelect.addEventListener('change', function () {
            const accountId = this.value;
            templateSelect.innerHTML = '<option value="">-- Select Template --</option>';
            templateInfo.classList.add('hidden');

            if (!accountId) return;

            fetch('{{ route("admin.accounts.templates", ":id") }}'.replace(':id', accountId))
                .then(r => r.json())
                .then(data => {
                    data.templates.forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.name + ' (' + t.language_code + ')';
                        opt.dataset.header = t.header_type;
                        opt.dataset.variables = JSON.stringify(t.body_variables || []);
                        templateSelect.appendChild(opt);
                    });
                })
                .catch(() => { });
        });

        templateSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];

            if (!opt || !opt.value) {
                templateInfo.classList.add('hidden');
                return;
            }

            let vars = [];

            try {
                vars = opt.dataset.variables
                    ? JSON.parse(opt.dataset.variables)
                    : [];
            } catch (e) {
                console.error('Invalid JSON in data-variables:', opt.dataset.variables);
            }

            let info = 'Header: ' + (opt.dataset.header || 'none');

            if (vars.length) {
                info += ' | Variables: @{{1}} = ' + vars.join(', @{{') + '}}';
            }

            templateInfo.textContent = info;
            templateInfo.classList.remove('hidden');
        });
    </script>
</body>

</html>