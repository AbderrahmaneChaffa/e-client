@extends('clients.layouts.app')

@section('page-title','Tableau de bord')

@section('content')
<div class="space-y-6">
    <!-- KPI row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-600">Nombre de factures</p>
            <p class="text-2xl font-bold text-gray-800 mt-2">{{ $totalInvoices }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-600">Total facturé</p>
            <p class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($totalFactured, 0, ',', ' ') }} DA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-600">Total payé</p>
            <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($totalPaid, 0, ',', ' ') }} DA</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <p class="text-sm text-gray-600">Reste à payer</p>
            <p class="text-2xl font-bold text-red-600 mt-2">{{ number_format($totalDue, 0, ',', ' ') }} DA</p>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Paiements / 6 mois</h3>
            <div class="chart-container">
                <canvas id="paymentChart" data-labels='@json($labels)' data-amounts='@json($amounts)'></canvas>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Statut des factures</h3>
            <div class="chart-container">
                <canvas id="invoiceStatusChart" data-paid="{{ $invoiceChartData['paid'] }}" data-unpaid="{{ $invoiceChartData['unpaid'] }}"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent invoices/payments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent invoices -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Factures récentes</h3>
                <a href="{{ route('client.factures.index') }}" class="text-sm text-blue-600 hover:underline">Voir tout</a>
            </div>
            <div class="space-y-3">
                @forelse($recentInvoices as $inv)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $inv->numero_facture }}</p>
                        <p class="text-xs text-gray-500">{{ $inv->date_facture->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-gray-800">{{ number_format($inv->total_ttc, 0, ',', ' ') }} DA</p>
                        <p class="text-xs {{ $inv->reste_a_payer <= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $inv->reste_a_payer <= 0 ? 'Payée' : 'Impayée' }}
                        </p>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500 py-6">Aucune facture récente</p>
                @endforelse
            </div>
        </div>
        <!-- Recent payments -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Paiements récents</h3>
                <a href="{{ route('client.paiements.index') }}" class="text-sm text-blue-600 hover:underline">Voir tout</a>
            </div>
            <div class="space-y-3">
                @forelse($recentPayments as $pay)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $pay->facture->numero_facture ?? '' }}</p>
                        <p class="text-xs text-gray-500">{{ $pay->date_paiement }}</p>
                    </div>
                    <div class="text-right text-green-600 font-bold">
                        + {{ number_format($pay->montant_verse, 0, ',', ' ') }} DA
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
        // charts same as admin code
        const paymentCanvas = document.getElementById('paymentChart');
        if (paymentCanvas) {
            new Chart(paymentCanvas, {
                type: 'line',
                data: {
                    labels: JSON.parse(paymentCanvas.dataset.labels),
                    datasets: [{
                        label: 'Paiements (DA)',
                        data: JSON.parse(paymentCanvas.dataset.amounts),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 244, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        const invoiceCanvas = document.getElementById('invoiceStatusChart');
        if (invoiceCanvas) {
            new Chart(invoiceCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Payées','Impayées'],
                    datasets: [{
                        data: [parseInt(invoiceCanvas.dataset.paid), parseInt(invoiceCanvas.dataset.unpaid)],
                        backgroundColor: ['#10b981','#ef4444']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
</script>
@endsection