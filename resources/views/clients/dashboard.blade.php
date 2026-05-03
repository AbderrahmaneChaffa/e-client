{{-- resources/views/clients/dashboard.blade.php --}}
@extends('clients.layouts.app')

@section('page-title', 'Mon Espace Client')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-6 md:px-8">

        {{-- ── En-tête personnalisé ────────────────────────────────────────────── --}}
        <div class="relative bg-gradient-to-r from-blue-700 via-blue-600 to-blue-500
                    rounded-2xl overflow-hidden mb-8 shadow-lg">
            {{-- Motif décoratif --}}
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" viewBox="0 0 800 200" preserveAspectRatio="xMidYMid slice">
                    <circle cx="700" cy="-50" r="200" fill="white"/>
                    <circle cx="650" cy="250" r="150" fill="white"/>
                    <circle cx="100" cy="180" r="100" fill="white"/>
                </svg>
            </div>

            <div class="relative px-6 py-8 md:px-10 flex flex-col md:flex-row
                        md:items-center md:justify-between gap-4">
                <div>
                    {{-- Logo EPO --}}
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 bg-white/20 backdrop-blur rounded-xl
                                    flex items-center justify-center">
                            <i class="fa-solid fa-ship text-white text-lg"></i>
                        </div>
                        <span class="text-blue-100 text-xs font-semibold uppercase tracking-widest">
                            Entreprise Portuaire d'Oran
                        </span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white">
                        Bonjour, {{ Auth::user()->name }} 👋
                    </h1>
                    <p class="text-blue-100 text-sm mt-1">
                        {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
                        · Client N° {{ Auth::user()->client?->code_client ?? '—' }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    {{-- Alerte impayés --}}
                    @if($unpaidCount > 0)
                        <div class="bg-red-500/20 backdrop-blur border border-red-300/30
                                    rounded-xl px-4 py-3 text-center">
                            <p class="text-red-100 text-xs font-medium">Factures impayées</p>
                            <p class="text-white text-2xl font-bold tabular-nums">{{ $unpaidCount }}</p>
                        </div>
                    @endif

                    <a href="{{ route('client.factures.index') }}"
                       class="inline-flex items-center gap-2 bg-white/20 hover:bg-white/30
                              backdrop-blur border border-white/30 text-white
                              text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
                        <i class="fa-solid fa-file-invoice"></i>
                        Mes factures
                    </a>
                </div>
            </div>
        </div>

        {{-- ── KPI Cards ────────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

            {{-- Total Facturé --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-file-invoice text-blue-600 dark:text-blue-400 text-sm"></i>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500 tabular-nums">
                        {{ number_format($totalCount, 0, ',', ' ') }} fact.
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Total Facturé
                </p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums leading-tight">
                    {{ number_format($totalFacture, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
            </div>

            {{-- Total Payé --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-sm"></i>
                    </div>
                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                        {{ number_format($recoveryRate, 1) }}%
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Total Payé
                </p>
                <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums leading-tight">
                    {{ number_format($totalPaye, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
                <div class="mt-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1">
                    <div class="h-1 rounded-full bg-emerald-500 transition-all duration-1000"
                         style="width: {{ min($recoveryRate, 100) }}%"></div>
                </div>
            </div>

            {{-- Reste à Payer --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-5 shadow-sm
                        {{ $totalDue > 0 ? 'border-l-4 border-l-red-500' : '' }}">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 {{ $totalDue > 0 ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-100 dark:bg-gray-700' }}
                                rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-clock {{ $totalDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }} text-sm"></i>
                    </div>
                    @if($totalDue > 0)
                        <span class="text-xs text-red-600 bg-red-50 dark:bg-red-900/30
                                     px-2 py-0.5 rounded-full font-medium animate-pulse">
                            En attente
                        </span>
                    @endif
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Reste à Payer
                </p>
                <p class="text-xl font-bold {{ $totalDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}
                          tabular-nums leading-tight">
                    {{ number_format($totalDue, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
            </div>

            {{-- Factures Payées --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-chart-pie text-purple-600 dark:text-purple-400 text-sm"></i>
                    </div>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Statut Factures
                </p>
                <div class="flex items-end gap-3 mt-1">
                    <div class="text-center">
                        <p class="text-lg font-bold text-emerald-600 tabular-nums">{{ $paidCount }}</p>
                        <p class="text-[10px] text-gray-400">Payées</p>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-gray-600"></div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-orange-500 tabular-nums">{{ $unpaidCount }}</p>
                        <p class="text-[10px] text-gray-400">Impayées</p>
                    </div>
                    @if($canceledCount > 0)
                        <div class="w-px h-8 bg-gray-200 dark:bg-gray-600"></div>
                        <div class="text-center">
                            <p class="text-lg font-bold text-gray-400 tabular-nums">{{ $canceledCount }}</p>
                            <p class="text-[10px] text-gray-400">Annulées</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Alerte factures impayées anciennes ──────────────────────────────── --}}
        @if($facturesEnRetard->count() > 0)
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800
                        rounded-2xl p-5 mb-8">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-red-800 dark:text-red-300 mb-3">
                            {{ $facturesEnRetard->count() }} facture(s) impayée(s) — merci de régulariser votre situation
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($facturesEnRetard as $f)
                                <a href="{{ route('client.factures.show', $f) }}"
                                   class="flex items-center justify-between bg-white dark:bg-gray-800
                                          border border-red-200 dark:border-red-700 rounded-xl
                                          px-3 py-2 hover:border-red-400 transition-colors group">
                                    <span class="font-mono text-xs font-semibold text-gray-700
                                                 dark:text-gray-300 group-hover:text-red-600 transition-colors">
                                        {{ $f->numero_facture }}
                                    </span>
                                    <span class="text-xs font-bold text-red-600 dark:text-red-400 tabular-nums">
                                        {{ number_format($f->reste_a_payer, 0, ',', ' ') }} DA
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Graphiques ───────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">

            {{-- Graphique ligne — 12 mois --}}
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Historique de paiements
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            12 derniers mois
                        </p>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs text-gray-400">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        Montant payé (DA)
                    </span>
                </div>
                <div class="h-52">
                    <canvas id="chartPaiements"
                            data-labels='@json($moisLabels)'
                            data-amounts='@json($moisAmounts)'></canvas>
                </div>
            </div>

            {{-- Donut --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">
                    Mes factures
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">Répartition</p>
                <div class="h-40">
                    <canvas id="chartDonut"
                            data-paid="{{ $paidCount }}"
                            data-unpaid="{{ $unpaidCount }}"
                            data-canceled="{{ $canceledCount }}"></canvas>
                </div>
                <div class="mt-5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>Payées
                        </span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $paidCount }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span>Impayées
                        </span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $unpaidCount }}</span>
                    </div>
                    @if($canceledCount > 0)
                        <div class="flex items-center justify-between text-xs">
                            <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>Annulées
                            </span>
                            <span class="font-bold text-gray-800 dark:text-gray-200">{{ $canceledCount }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Factures + Paiements récents ────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Factures récentes --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                            dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-blue-500"></i>
                        Factures Récentes
                    </h3>
                    <a href="{{ route('client.factures.index') }}"
                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                        Voir tout →
                    </a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($recentInvoices as $inv)
                                <a href="{{ route('client.factures.show', $inv) }}"
                                   class="flex items-center gap-4 px-6 py-3.5 hover:bg-gray-50
                                          dark:hover:bg-gray-700/30 transition-colors group">

                                    {{-- Icône statut --}}
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                                                {{ $inv->reste_a_payer <= 0
                        ? 'bg-emerald-100 dark:bg-emerald-900/40'
                        : 'bg-orange-100 dark:bg-orange-900/40' }}">
                                        <i class="fa-solid {{ $inv->reste_a_payer <= 0 ? 'fa-circle-check text-emerald-600' : 'fa-clock text-orange-500' }}
                                                  text-xs"></i>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200
                                                  group-hover:text-blue-600 dark:group-hover:text-blue-400
                                                  font-mono truncate">
                                            {{ $inv->numero_facture }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ $inv->date_facture?->format('d/m/Y') ?? '—' }}
                                            @if($inv->description)
                                                · {{ Str::limit($inv->description, 25) }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="text-right flex-shrink-0">
                                        <p class="text-sm font-bold text-gray-800 dark:text-gray-200 tabular-nums">
                                            {{ number_format($inv->total_ttc, 0, ',', ' ') }} DA
                                        </p>
                                        @if($inv->reste_a_payer > 0)
                                            <p class="text-xs text-red-500 font-medium tabular-nums">
                                                Reste: {{ number_format($inv->reste_a_payer, 0, ',', ' ') }} DA
                                            </p>
                                        @else
                                            <p class="text-xs text-emerald-600 font-medium">Soldée</p>
                                        @endif
                                    </div>
                                </a>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <i class="fa-solid fa-inbox text-3xl text-gray-200 dark:text-gray-600 mb-2 block"></i>
                            <p class="text-sm text-gray-400 dark:text-gray-500">Aucune facture</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Paiements récents --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                            dark:border-gray-700">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-coins text-emerald-500"></i>
                        Paiements Récents
                    </h3>
                    <a href="{{ route('client.paiements.index') }}"
                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                        Voir tout →
                    </a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($recentPayments as $pay)
                        <div class="flex items-center gap-4 px-6 py-3.5">

                            {{-- Icône paiement --}}
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/40
                                        flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-arrow-down-to-line text-emerald-600
                                          dark:text-emerald-400 text-xs"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200
                                          font-mono truncate">
                                    {{ $pay->facture?->numero_facture ?? '—' }}
                                </p>
                                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    @if($pay->recu)
                                        <span>Reçu N° {{ $pay->recu }}</span>
                                        <span>·</span>
                                    @endif
                                    @if($pay->banque)
                                        <span>{{ $pay->banque }}</span>
                                        <span>·</span>
                                    @endif
                                    <span>{{ $pay->date_paiement ?? '—' }}</span>
                                </div>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    + {{ number_format($pay->montant, 0, ',', ' ') }} DA
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <i class="fa-solid fa-inbox text-3xl text-gray-200 dark:text-gray-600 mb-2 block"></i>
                            <p class="text-sm text-gray-400 dark:text-gray-500">Aucun paiement</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Footer EPO ───────────────────────────────────────────────────────── --}}
        <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                <i class="fa-solid fa-ship mr-1"></i>
                Entreprise Portuaire d'Oran — Port d'Oran, Algérie ·
                <a href="mailto:contact@epo.dz" class="hover:text-blue-500 transition-colors">
                    contact@epo.dz
                </a>
            </p>
        </div>
    </div>

    {{-- @push('scripts') --}}
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark    = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
            const textColor = isDark ? '#9ca3af' : '#6b7280';
            const tooltipBg = isDark ? '#1f2937' : '#ffffff';
            const tooltipTx = isDark ? '#f3f4f6' : '#111827';

            // ── Graphique ligne paiements ─────────────────────────────────────────
            const cl = document.getElementById('chartPaiements');
            if (cl) {
                const labels  = JSON.parse(cl.dataset.labels);
                const amounts = JSON.parse(cl.dataset.amounts);

                new Chart(cl, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Paiements (DA)',
                            data: amounts,
                            borderColor: '#3b82f6',
                            backgroundColor: (ctx) => {
                                const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, 180);
                                g.addColorStop(0, 'rgba(59,130,246,0.2)');
                                g.addColorStop(1, 'rgba(59,130,246,0)');
                                return g;
                            },
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: isDark ? '#1f2937' : '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleColor: tooltipTx,
                                bodyColor: textColor,
                                borderColor: isDark ? '#374151' : '#e5e7eb',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: ctx => ' ' + ctx.parsed.y.toLocaleString('fr-DZ') + ' DA'
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor, drawBorder: false },
                                ticks: { color: textColor, font: { size: 10 } }
                            },
                            y: {
                                grid: { color: gridColor, drawBorder: false },
                                ticks: {
                                    color: textColor,
                                    font: { size: 10 },
                                    callback: v => {
                                        if (v >= 1_000_000) return (v/1_000_000).toFixed(1) + 'M';
                                        if (v >= 1_000)     return (v/1_000).toFixed(0) + 'k';
                                        return v;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // ── Donut statut ──────────────────────────────────────────────────────
            const cd = document.getElementById('chartDonut');
            if (cd) {
                const paid     = parseInt(cd.dataset.paid     || 0);
                const unpaid   = parseInt(cd.dataset.unpaid   || 0);
                const canceled = parseInt(cd.dataset.canceled || 0);

                new Chart(cd, {
                    type: 'doughnut',
                    data: {
                        labels: ['Payées', 'Impayées', 'Annulées'],
                        datasets: [{
                            data: [paid, unpaid, canceled],
                            backgroundColor: ['#10b981', '#fb923c', '#d1d5db'],
                            borderColor: isDark ? '#1f2937' : '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 5,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: tooltipBg,
                                titleColor: tooltipTx,
                                bodyColor: textColor,
                                borderColor: isDark ? '#374151' : '#e5e7eb',
                                borderWidth: 1,
                                padding: 10,
                                callbacks: {
                                    label: ctx => ' ' + ctx.parsed + ' factures'
                                }
                            }
                        }
                    }
                });
            }
        });
        </script>
    {{-- @endpush --}}
@endsection