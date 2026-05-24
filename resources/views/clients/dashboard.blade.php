{{-- // VIEW: client.dashboard --}}
{{-- // ROLE: client --}}
{{-- // COMPONENTS: <x-page-header>, <x-stat-card>, <x-badge>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Mon espace client';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="{ state: 'data' }" class="space-y-6">
    <section class="rounded-lg border border-primary-200 bg-primary-600 p-6 text-white shadow-soft dark:border-primary-900">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-100">Entreprise Portuaire d'Oran</p>
                <h1 class="mt-2 text-2xl font-bold md:text-3xl">Bonjour, {{ Auth::user()->name }} 👋</h1>
                <p class="mt-1 text-sm text-primary-100">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }} · Client {{ Auth::user()->client?->code_client ?? '-' }}</p>
            </div>
            @if($unpaidCount > 0)
                <div class="rounded-lg border border-white/20 bg-white/10 p-4 text-center backdrop-blur">
                    <p class="text-xs font-medium text-primary-100">Factures impayees</p>
                    <p class="text-3xl font-bold">{{ $unpaidCount }}</p>
                </div>
            @endif
        </div>
    </section>

    <div x-show="state === 'loading'" x-cloak>
        <x-loading-skeleton rows="4" />
    </div>

    <div class="space-y-6 transition-all duration-300">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Mes factures" :value="number_format($totalCount, 0, ',', ' ')" icon="file-text" color="primary" trend-value="{{ $unpaidCount }} en cours" />
            <x-stat-card title="Total facture" :value="number_format($totalFacture, 0, ',', ' ').' DA'" icon="receipt-text" color="info" />
            <x-stat-card title="Total paye" :value="number_format($totalPaye, 0, ',', ' ').' DA'" icon="circle-check" color="success" trend="up" trend-value="{{ number_format($recoveryRate, 1) }}%" />
            <x-stat-card title="Reste a payer" :value="number_format($totalDue, 0, ',', ' ').' DA'" icon="badge-alert" color="{{ $totalDue > 0 ? 'danger' : 'success' }}" />
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="ui-card p-5 xl:col-span-2">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paiements sur 12 mois</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Evolution de vos reglements.</p>
                    </div>
                </div>
                <div class="h-72">
                    <canvas id="clientPaymentsChart" aria-label="Graphique paiements client" role="img"></canvas>
                </div>
            </article>

            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Recouvrement</h2>
                <div class="mt-6 flex justify-center">
                    <div class="relative h-40 w-40 rounded-full bg-gray-100 dark:bg-gray-700" style="background: conic-gradient(#10B981 {{ min($recoveryRate, 100) }}%, rgba(148,163,184,.25) 0)">
                        <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white dark:bg-gray-800">
                            <span class="text-3xl font-bold">{{ number_format($recoveryRate, 1) }}%</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">paye</span>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <article class="ui-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Mes factures recentes</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recentInvoices->count() }} dernieres factures</p>
                    </div>
                    <a href="{{ route('client.factures.index') }}" class="text-sm font-semibold text-primary-600 dark:text-primary-300">Voir tout</a>
                </div>
                @if($recentInvoices->isEmpty())
                    <div class="p-4"><x-empty-state icon="file-text" title="Aucune facture" message="Vos factures apparaitront ici." /></div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentInvoices as $invoice)
                            @php
                                $status = $invoice->annuler ? 'annulee' : ((float) $invoice->reste_a_payer <= 0 ? 'paye' : 'impaye');
                            @endphp
                            <a href="{{ route('client.factures.show', $invoice) }}" class="block px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">#{{ $invoice->numero_facture }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ optional($invoice->date_facture)->format('d/m/Y') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold">{{ number_format($invoice->total_ttc, 0, ',', ' ') }} DA</p>
                                        <x-badge :status="$status" />
                                    </div>
                                </div>
                                @if($status === 'impaye')
                                    @php
                                        $paidPercent = $invoice->total_ttc > 0 ? min(100, (($invoice->total_ttc - $invoice->reste_a_payer) / $invoice->total_ttc) * 100) : 0;
                                    @endphp
                                    <div class="mt-3 h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-2 rounded-full bg-primary-600" style="width: {{ $paidPercent }}%"></div>
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="ui-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paiements recents</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $recentPayments->count() }} reglements</p>
                    </div>
                    <a href="{{ route('client.paiements.index') }}" class="text-sm font-semibold text-primary-600 dark:text-primary-300">Voir tout</a>
                </div>
                @if($recentPayments->isEmpty())
                    <div class="p-4"><x-empty-state icon="credit-card" title="Aucun paiement" message="Vos paiements apparaitront ici." /></div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recentPayments as $payment)
                            <div class="flex items-center justify-between gap-3 px-5 py-4">
                                <div>
                                    <p class="font-semibold">Recu {{ $payment->recu ?? '-' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->date_paiement ? \Illuminate\Support\Carbon::parse($payment->date_paiement)->format('d/m/Y') : '-' }}</p>
                                </div>
                                <p class="font-bold text-success-700 dark:text-success-300">{{ number_format($payment->montant, 0, ',', ' ') }} DA</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        @if($facturesEnRetard->isNotEmpty())
            <section class="ui-card p-5">
                <div class="mb-4 flex items-center gap-2 text-danger-700 dark:text-danger-300">
                    <i data-lucide="triangle-alert" class="h-5 w-5" aria-hidden="true"></i>
                    <h2 class="font-semibold">Factures a regulariser</h2>
                </div>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                    @foreach($facturesEnRetard as $invoice)
                        <a href="{{ route('client.factures.show', $invoice) }}" class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-900 dark:border-danger-900/60 dark:bg-danger-900/20 dark:text-danger-100">
                            <p class="font-semibold">#{{ $invoice->numero_facture }}</p>
                            <p class="mt-1 text-sm">{{ number_format($invoice->reste_a_payer, 0, ',', ' ') }} DA</p>
                            <p class="mt-1 text-xs opacity-75">{{ optional($invoice->date_facture)->format('d/m/Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('clientPaymentsChart');
        if (!window.Chart || !canvas) return;
        const dark = document.documentElement.classList.contains('dark');
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: @js($moisLabels),
                datasets: [{
                    label: 'Paiements',
                    data: @js($moisAmounts),
                    backgroundColor: '#4F46E5',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800 },
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: dark ? '#CBD5E1' : '#475569' }, grid: { display: false } },
                    y: { ticks: { color: dark ? '#CBD5E1' : '#475569' }, grid: { color: dark ? 'rgba(148,163,184,.18)' : 'rgba(148,163,184,.28)' } }
                }
            }
        });
    });
</script>
@endpush
