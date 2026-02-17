@extends('admins.layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">Tableau de Bord</h2>
            <p class="text-gray-500 mt-2">Vue d'ensemble de l'application E-Client</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-bold text-blue-600">{{ $totalClients }} Clients</p>
            <p class="text-sm text-gray-500">Actifs dans le système</p>
        </div>
    </div>

    <!-- KPI Cards - Row 1 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Facturé -->
        <div class="stat-card bg-white rounded-lg shadow-md border border-gray-100 p-6" style="--color-start: #3b82f6; --color-end: #1e40af;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Facturé</p>
                    <p class="text-3xl font-bold text-blue-900 mt-2">{{ number_format($stats->total_facture ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-700 mt-2">{{ $stats->total_count ?? 0 }} factures</p>
                </div>
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Encaissé -->
        <div class="stat-card bg-white rounded-lg shadow-md border border-gray-100 p-6" style="--color-start: #10b981; --color-end: #059669;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Encaissé</p>
                    <p class="text-3xl font-bold text-green-800 mt-2">{{ number_format($stats->total_encaisse ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500 mt-2 font-semibold">✓ Paiements reçus</p>
                </div>
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-credit-card text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Impayé -->
        <div class="stat-card bg-white rounded-lg shadow-md border border-gray-100 p-6" style="--color-start: #ef4444; --color-end: #dc2626;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Reste à Payer</p>
                    <p class="text-3xl font-bold text-red-800 mt-2">{{ number_format($stats->total_impayes ?? 0, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $unpaidInvoices ?? 0 }} factures impayées</p>
                </div>
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Taux de Recouvrement -->
        <div class="stat-card bg-white rounded-lg shadow-md border border-gray-100 p-6" style="--color-start: #f59e0b; --color-end: #d97706;">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Taux Recouvrement</p>
                    <p class="text-3xl font-bold text-amber-600 mt-2">{{ number_format($recoveryRate, 1) }}%</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-amber-500 h-2 rounded-full recovery-bar" data-width="{{ min($recoveryRate, 100) }}"></div>
                    </div>
                </div>
                <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-chart-pie text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Clients -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Utilisateurs</h3>
                <i class="fa-solid fa-users text-2xl text-purple-500"></i>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total</span>
                    <span class="font-bold text-gray-800">{{ $totalUsers }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Administrateurs</span>
                    <span class="font-bold text-blue-600">{{ $totalAdmins }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Clients</span>
                    <span class="font-bold text-green-600">{{ $totalClientUsers }}</span>
                </div>
            </div>
        </div>

        <!-- Factures Stats -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Factures</h3>
                <i class="fa-solid fa-file-invoice text-2xl text-blue-500"></i>
            </div>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Total</span>
                    <span class="font-bold text-gray-800">{{ $totalInvoices }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Payées</span>
                    <span class="font-bold text-green-600">{{ $paidInvoices }} <span class="text-xs text-gray-500">({{ $totalInvoices > 0 ? round(($paidInvoices / $totalInvoices) * 100) : 0 }}%)</span></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Impayées</span>
                    <span class="font-bold text-red-600">{{ $unpaidInvoices }} <span class="text-xs text-gray-500">({{ $totalInvoices > 0 ? round(($unpaidInvoices / $totalInvoices) * 100) : 0 }}%)</span></span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Actions Rapides</h3>
                <i class="fa-solid fa-bolt text-2xl text-yellow-500"></i>
            </div>
            <div class="space-y-2">
                <a href="{{ route('admin.clients.create') }}" class="block px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-plus mr-2"></i> Nouveau Client
                </a>
                <a href="{{ route('admin.imports.index') }}" class="block px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-upload mr-2"></i> Importer Factures
                </a>
                <a href="{{ route('admin.clients.index') }}" class="block px-4 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 rounded-lg text-sm font-medium transition">
                    <i class="fa-solid fa-list mr-2"></i> Gérer Clients
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Évolution des Paiements -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Évolution des Paiements (6 mois)</h3>
            <div class="chart-container">
                <canvas id="paymentChart" data-labels='@json($labels)' data-amounts='@json($amounts)'></canvas>
            </div>
        </div>

        <!-- Statut Factures -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Statut des Factures</h3>
            <div class="chart-container">
                <canvas id="invoiceStatusChart" data-paid="{{ $paidInvoices }}" data-unpaid="{{ $unpaidInvoices }}"></canvas>
            </div>
        </div>
    </div>

    <!-- Debtors and Payers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Débiteurs -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Top 5 Clients Débiteurs</h3>
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs text-gray-600 uppercase font-semibold">
                            <th class="pb-3 text-left">Client</th>
                            <th class="pb-3 text-right">Reste à Payer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDebiteurs as $index => $client)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 bg-red-100 text-red-600 rounded-full flex items-center justify-center font-bold text-xs">{{ $index + 1 }}</span>
                                    <a href="{{ route('admin.clients.show', $client) }}" class="font-medium text-gray-800 hover:text-blue-600">{{ $client->name }}</a>
                                </div>
                            </td>
                            <td class="py-3 text-right text-red-600 font-bold">{{ number_format($client->factures_sum_reste_a_payer ?? 0, 0, ',', ' ') }} DA</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="py-6 text-center text-gray-500">
                                <i class="fa-solid fa-inbox text-2xl opacity-50"></i>
                                <p class="mt-2">Aucun débiteur</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Payeurs -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Top 5 Meilleurs Payeurs</h3>
                <i class="fa-solid fa-star text-yellow-500"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs text-gray-600 uppercase font-semibold">
                            <th class="pb-3 text-left">Client</th>
                            <th class="pb-3 text-right">Total Payé</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topPayeurs as $index => $client)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-xs">{{ $index + 1 }}</span>
                                    <a href="{{ route('admin.clients.show', $client) }}" class="font-medium text-gray-800 hover:text-blue-600">{{ $client->name }}</a>
                                </div>
                            </td>
                            <td class="py-3 text-right text-green-600 font-bold">{{ number_format($client->factures_sum_montant_paye ?? 0, 0, ',', ' ') }} DA</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="py-6 text-center text-gray-500">
                                <i class="fa-solid fa-inbox text-2xl opacity-50"></i>
                                <p class="mt-2">Aucun paiement</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Invoices -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Factures Récentes</h3>
                <a href="{{ route('admin.factures.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Voir tout →</a>
            </div>
            <div class="space-y-3">
                @forelse($recentInvoices as $invoice)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">{{ $invoice->numero_facture }}</p>
                        <p class="text-xs text-gray-600">{{ $invoice->client->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">{{ number_format($invoice->total_ttc, 0, ',', ' ') }} DA</p>
                        <p class="text-xs {{ $invoice->reste_a_payer <= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $invoice->reste_a_payer <= 0 ? '✓ Payée' : 'Impayée' }}
                        </p>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 py-6">Aucune facture récente</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-lg shadow-md border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">Paiements Récents</h3>
                <a href="{{ route('admin.paiements.index') }}" class="text-sm text-blue-600 hover:text-blue-800">Voir tout →</a>
            </div>
            <div class="space-y-3">
                @forelse($recentPayments as $payment)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800">{{ $payment->facture->numero_facture ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-600">{{ $payment->facture->client->name ?? 'Client' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600">+ {{ number_format($payment->montant, 0, ',', ' ') }} DA</p>
                        <p class="text-xs text-gray-500">{{ $payment->date_paiement }}</p>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 py-6">Aucun paiement récent</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Set recovery bar width
        const recoveryBars = document.querySelectorAll('.recovery-bar');
        recoveryBars.forEach(bar => {
            const width = bar.dataset.width;
            bar.style.width = width + '%';
        });

        // Payment Chart (Line)
        const paymentCanvas = document.getElementById('paymentChart');
        if (paymentCanvas) {
            new Chart(paymentCanvas, {
                type: 'line',
                data: {
                    labels: JSON.parse(paymentCanvas.dataset.labels),
                    datasets: [{
                        label: 'Paiements Reçus (DA)',
                        data: JSON.parse(paymentCanvas.dataset.amounts),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 244, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('fr-FR') + ' DA';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Invoice Status Chart (Doughnut)
        const invoiceCanvas = document.getElementById('invoiceStatusChart');
        if (invoiceCanvas) {
            new Chart(invoiceCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Payées', 'Impayées'],
                    datasets: [{
                        data: [
                            parseInt(invoiceCanvas.dataset.paid),
                            parseInt(invoiceCanvas.dataset.unpaid)
                        ],
                        backgroundColor: ['#10b981', '#ef4444'],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection