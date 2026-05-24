{{-- // VIEW: admin.dashboard --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-stat-card>, <x-badge>, <x-avatar>, <x-loading-skeleton>, <x-empty-state> --}}
{{-- // FILTERS: period selectors on charts, export image buttons --}}
@php
    $pageTitle = 'Tableau de bord admin';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="{ state: 'data' }" class="space-y-6">
    <x-page-header
        title="Tableau de bord"
        subtitle="Vue d'ensemble des factures, encaissements et imports ERP BIG."
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Dashboard']]"
    >
        <a href="{{ route('admin.imports.index') }}" class="ui-btn-primary">
            <i data-lucide="upload-cloud" class="h-4 w-4" aria-hidden="true"></i>
            Importer
        </a>
    </x-page-header>

    <div x-show="state === 'loading'" x-cloak>
        <x-loading-skeleton rows="4" />
    </div>

    <div x-show="state === 'error'" x-cloak class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i data-lucide="circle-alert" class="h-5 w-5" aria-hidden="true"></i>
                <p class="text-sm font-medium">Impossible de charger les indicateurs.</p>
            </div>
            <button type="button" class="ui-btn-secondary" @click="state = 'data'">Reessayer</button>
        </div>
    </div>

    <div class="space-y-6 transition-all duration-300">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Total facture" :value="number_format($stats->total_facture ?? 0, 0, ',', ' ').' DA'" icon="file-text" color="info" trend="up" trend-value="+{{ $weekStats['factures'] ?? 0 }} cette semaine" />
            <x-stat-card title="Total encaisse" :value="number_format($stats->total_encaisse ?? 0, 0, ',', ' ').' DA'" icon="circle-check" color="success" trend="up" trend-value="{{ number_format($recoveryRate, 1) }}%" />
            <x-stat-card title="Reste a payer" :value="number_format($stats->total_impayes ?? 0, 0, ',', ' ').' DA'" icon="badge-alert" color="danger" trend="down" trend-value="{{ $unpaidInvoices }} impayees" />
            <x-stat-card title="Clients actifs" :value="number_format($totalClients, 0, ',', ' ')" icon="users" color="primary" trend="up" trend-value="+{{ $weekStats['clients'] ?? 0 }} cette semaine" />
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="ui-card p-5 xl:col-span-2">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Encaissements sur 12 mois</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Montants regles par mois.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <select class="ui-input h-9 w-36">
                            <option>12 derniers mois</option>
                            <option>6 derniers mois</option>
                        </select>
                        <button type="button" class="ui-icon-btn" onclick="downloadChart('paymentsChart')" aria-label="Exporter le graphique">
                            <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="paymentsChart" aria-label="Graphique encaissements" role="img"></canvas>
                </div>
            </article>

            <article class="ui-card p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Statuts factures</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Repartition actuelle.</p>
                    </div>
                    <button type="button" class="ui-icon-btn" onclick="downloadChart('statusChart')" aria-label="Exporter le graphique">
                        <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="h-80">
                    <canvas id="statusChart" aria-label="Graphique statuts" role="img"></canvas>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="ui-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Dernieres factures</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recentInvoices->count() }} dernieres lignes</p>
                    </div>
                    <a href="{{ route('admin.factures.index') }}" class="text-sm font-semibold text-primary-600 dark:text-primary-300">Voir tout</a>
                </div>
                @if($recentInvoices->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="file-text" title="Aucune facture" message="Les factures importees apparaitront ici." />
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentInvoices as $invoice)
                            @php
                                $status = $invoice->annuler ? 'annulee' : ((float) $invoice->reste_a_payer <= 0 ? 'paye' : 'impaye');
                            @endphp
                            <a href="{{ route('admin.factures.show', $invoice) }}" class="flex items-center justify-between gap-4 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">#{{ $invoice->numero_facture }}</p>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $invoice->client?->name ?? 'Client inconnu' }} · {{ optional($invoice->date_facture)->format('d/m/Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($invoice->total_ttc, 0, ',', ' ') }} DA</p>
                                    <x-badge :status="$status" />
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="ui-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paiements recents</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recentPayments->count() }} paiements</p>
                    </div>
                    <a href="{{ route('admin.paiements.index') }}" class="text-sm font-semibold text-primary-600 dark:text-primary-300">Voir tout</a>
                </div>
                @if($recentPayments->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="credit-card" title="Aucun paiement" message="Les reglements importes apparaitront ici." />
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentPayments as $payment)
                            <div class="flex items-center justify-between gap-4 px-5 py-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-avatar :name="$payment->facture?->client?->name ?? 'Client'" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $payment->facture?->client?->name ?? 'Client inconnu' }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">Recu {{ $payment->recu ?? '-' }} · {{ $payment->date_paiement ? \Illuminate\Support\Carbon::parse($payment->date_paiement)->format('d/m/Y') : '-' }}</p>
                                    </div>
                                </div>
                                <p class="text-sm font-semibold tabular-nums text-success-700 dark:text-success-300">{{ number_format($payment->montant, 0, ',', ' ') }} DA</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Qualite des donnees</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Derniere verification globale.</p>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between"><span>Statut</span><x-badge :status="$verificationStatus['status'] ?? 'pending'" /></div>
                    <div class="flex items-center justify-between"><span>Anomalies</span><span class="font-semibold">{{ $dataHealth['issues_count'] ?? $dataHealth['critical_count'] ?? 0 }}</span></div>
                    <div class="flex items-center justify-between"><span>Progression</span><span class="font-semibold">{{ $verificationStatus['percentage'] ?? 0 }}%</span></div>
                </div>
                <form method="POST" action="{{ route('admin.imports.verify-global') }}" class="mt-5">
                    @csrf
                    <button type="submit" class="ui-btn-secondary w-full">
                        <i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i>
                        Verifier maintenant
                    </button>
                </form>
            </article>

            <article class="ui-card p-5 xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Imports recents</h2>
                    <a href="{{ route('admin.imports.index') }}" class="text-sm font-semibold text-primary-600 dark:text-primary-300">Historique</a>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    @forelse($recentImports as $batch)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $batch->original_filename }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $batch->type)) }} · {{ optional($batch->created_at)->format('d/m/Y H:i') }}</p>
                                </div>
                                <x-badge :status="$batch->status" />
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="upload-cloud" title="Aucun import" message="Deposez votre premier export Excel." :action-route="route('admin.imports.index')" action-label="Importer" />
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function chartColors() {
        const dark = document.documentElement.classList.contains('dark');
        return {
            grid: dark ? 'rgba(148,163,184,.18)' : 'rgba(148,163,184,.28)',
            text: dark ? '#CBD5E1' : '#475569',
        };
    }

    function downloadChart(id) {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const link = document.createElement('a');
        link.download = `${id}.png`;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const colors = chartColors();
        const paymentsCanvas = document.getElementById('paymentsChart');
        const statusCanvas = document.getElementById('statusChart');

        if (window.Chart && paymentsCanvas) {
            new Chart(paymentsCanvas, {
                type: 'line',
                data: {
                    labels: @js($moisLabels),
                    datasets: [{
                        label: 'Encaissements',
                        data: @js($moisAmounts),
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79,70,229,.12)',
                        fill: true,
                        tension: .35,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 800 },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: colors.text }, grid: { color: colors.grid } },
                        y: { ticks: { color: colors.text }, grid: { color: colors.grid } }
                    }
                }
            });
        }

        if (window.Chart && statusCanvas) {
            new Chart(statusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Payees', 'Impayees', 'Annulees'],
                    datasets: [{
                        data: [@js($paidInvoices), @js($unpaidInvoices), @js($canceledInvoices)],
                        backgroundColor: ['#10B981', '#EF4444', '#64748B'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 800 },
                    plugins: { legend: { position: 'bottom', labels: { color: colors.text } } }
                }
            });
        }
    });
</script>
@endpush
