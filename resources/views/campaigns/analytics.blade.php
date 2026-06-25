<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - {{ $campaign->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="{{ route('campaigns.show', $campaign) }}"
                        class="hover:bg-green-700 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Campaign
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
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            <i class="fas fa-chart-line text-green-600 mr-2"></i>Campaign Analytics
        </h2>

        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <!-- Status Breakdown -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Message Status Breakdown</h3>
                <canvas id="statusChart" height="250"></canvas>
            </div>

            <!-- Failure Reasons -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Failure Reasons</h3>
                @if (!empty($failureBreakdown))
                    <canvas id="failureChart" height="250"></canvas>
                @else
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-check-circle text-4xl mb-2"></i>
                        <p>No failures recorded</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="bg-white rounded-xl shadow-md p-6 mb-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Detailed Summary</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Metric</th>
                            <th class="px-4 py-3 text-right">Count</th>
                            <th class="px-4 py-3 text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $total = $campaign->total_numbers; @endphp
                        <tr>
                            <td class="px-4 py-3"><i class="fas fa-users text-gray-400 mr-2"></i>Total Numbers</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $total }}</td>
                            <td class="px-4 py-3 text-right">100%</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><i class="fas fa-paper-plane text-blue-400 mr-2"></i>Sent</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $campaign->sent_count }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ $total > 0 ? round(($campaign->sent_count / $total) * 100, 1) : 0 }}%</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><i class="fas fa-check-circle text-green-400 mr-2"></i>Delivered</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $campaign->delivered_count }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ $total > 0 ? round(($campaign->delivered_count / $total) * 100, 1) : 0 }}%</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><i class="fas fa-eye text-purple-400 mr-2"></i>Read</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $campaign->read_count }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ $total > 0 ? round(($campaign->read_count / $total) * 100, 1) : 0 }}%</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3"><i class="fas fa-times-circle text-red-400 mr-2"></i>Failed</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $campaign->failed_count }}</td>
                            <td class="px-4 py-3 text-right">
                                {{ $total > 0 ? round(($campaign->failed_count / $total) * 100, 1) : 0 }}%</td>
                        </tr>
                        <tr class="bg-green-50">
                            <td class="px-4 py-3 font-bold"><i
                                    class="fas fa-percentage text-green-600 mr-2"></i>Delivery Rate</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">
                                {{ $campaign->sent_count > 0 ? round(($campaign->delivered_count / $campaign->sent_count) * 100, 1) : 0 }}%
                            </td>
                            <td class="px-4 py-3 text-right">-</td>
                        </tr>
                        <tr class="bg-purple-50">
                            <td class="px-4 py-3 font-bold"><i class="fas fa-book-open text-purple-600 mr-2"></i>Read
                                Rate</td>
                            <td class="px-4 py-3 text-right font-bold text-purple-600">
                                {{ $campaign->delivered_count > 0 ? round(($campaign->read_count / $campaign->delivered_count) * 100, 1) : 0 }}%
                            </td>
                            <td class="px-4 py-3 text-right">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Failure Details -->
        @if (!empty($failureBreakdown))
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Failure Breakdown</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Reason</th>
                                <th class="px-4 py-3 text-right">Count</th>
                                <th class="px-4 py-3 text-right">% of Failures</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach ($failureBreakdown as $reason => $count)
                                <tr>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold bg-red-100 text-red-800">
                                            {{ $reason }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold">{{ $count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        {{ $campaign->failed_count > 0 ? round(($count / $campaign->failed_count) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($statusBreakdown)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($statusBreakdown)) !!},
                    backgroundColor: [
                        '#10B981', '#3B82F6', '#8B5CF6', '#EF4444', '#F59E0B',
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Failure Chart
        @if (!empty($failureBreakdown))
            const failureCtx = document.getElementById('failureChart').getContext('2d');
            new Chart(failureCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(array_keys($failureBreakdown)) !!},
                    datasets: [{
                        label: 'Failures',
                        data: {!! json_encode(array_values($failureBreakdown)) !!},
                        backgroundColor: '#EF4444'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        @endif
    </script>
</body>

</html>
