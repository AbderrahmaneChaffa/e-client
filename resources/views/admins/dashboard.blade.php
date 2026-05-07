{{-- resources/views/admins/dashboard.blade.php --}}
@extends('admins.layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 px-4 py-6 md:px-8">

    {{-- ── En-tête ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">
                Entreprise Portuaire d'Oran
            </p>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Tableau de bord
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Activité semaine --}}
            <div class="hidden md:flex items-center gap-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5">
                <div class="text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Cette semaine</p>
                    <div class="flex items-center gap-3 mt-1">
                        <span class="flex items-center gap-1 text-xs font-semibold text-blue-600">
                            <i class="fa-solid fa-file-invoice text-[10px]"></i>
                            {{ $weekStats['factures'] }} fact.
                        </span>
                        <span class="flex items-center gap-1 text-xs font-semibold text-green-600">
                            <i class="fa-solid fa-coins text-[10px]"></i>
                            {{ $weekStats['paiements'] }} paiem.
                        </span>
                    </div>
                </div>
            </div>

            <a href="{{ route('admin.imports.index') }}"
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white
                      text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
                <i class="fa-solid fa-upload"></i>
                <span class="hidden sm:inline">Importer ERP BIG</span>
            </a>
        </div>
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

        {{-- Total Facturé --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow
                    relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-transparent
                        dark:from-blue-900/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 bg-blue-100 dark:bg-blue-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-file-invoice text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <span class="text-xs font-medium text-blue-600 dark:text-blue-400
                                 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-full">
                        Total
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Total Facturé
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ number_format($stats->total_facture ?? 0, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ number_format($totalInvoices, 0, ',', ' ') }} factures actives
                </p>
            </div>
        </div>

        {{-- Total Encaissé --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow
                    relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-transparent
                        dark:from-emerald-900/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400"></i>
                    </div>
                    <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400
                                 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full">
                        Reçu
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Total Encaissé
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ number_format($stats->total_encaisse ?? 0, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ number_format($paidInvoices, 0, ',', ' ') }} factures soldées
                </p>
            </div>
        </div>

        {{-- Reste à Payer --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow
                    relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-red-50 to-transparent
                        dark:from-red-900/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 bg-red-100 dark:bg-red-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-clock text-red-600 dark:text-red-400"></i>
                    </div>
                    <span class="text-xs font-medium text-red-600 dark:text-red-400
                                 bg-red-50 dark:bg-red-900/30 px-2 py-0.5 rounded-full">
                        En cours
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Reste à Payer
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ number_format($stats->total_impayes ?? 0, 0, ',', ' ') }}
                    <span class="text-sm font-medium text-gray-400">DA</span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    {{ number_format($unpaidInvoices, 0, ',', ' ') }} factures impayées
                </p>
            </div>
        </div>

        {{-- Taux de Recouvrement --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-5 shadow-sm hover:shadow-md transition-shadow
                    relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-transparent
                        dark:from-amber-900/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-11 h-11 bg-amber-100 dark:bg-amber-900/40 rounded-xl
                                flex items-center justify-center">
                        <i class="fa-solid fa-chart-pie text-amber-600 dark:text-amber-400"></i>
                    </div>
                    <span class="text-xs font-medium
                                 {{ $recoveryRate >= 75 ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : ($recoveryRate >= 50 ? 'text-amber-600 bg-amber-50 dark:bg-amber-900/30' : 'text-red-600 bg-red-50 dark:bg-red-900/30') }}
                                 px-2 py-0.5 rounded-full">
                        {{ $recoveryRate >= 75 ? 'Bon' : ($recoveryRate >= 50 ? 'Moyen' : 'Faible') }}
                    </span>
                </div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
                    Taux Recouvrement
                </p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">
                    {{ number_format($recoveryRate, 1) }}
                    <span class="text-sm font-medium text-gray-400">%</span>
                </p>
                <div class="mt-3">
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full transition-all duration-1000
                                    {{ $recoveryRate >= 75 ? 'bg-emerald-500' : ($recoveryRate >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                             style="width: {{ min($recoveryRate, 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-blue-500"></i>
                    Integrite des donnees
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Derniere verification: {{ $dataHealth['last_verified_at']?->format('d/m/Y H:i') ?? 'jamais' }}
                </p>
            </div>
            <form x-data="{loading:false}" @submit="loading=true" method="POST" action="{{ route('admin.imports.verify-global') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold">
                    <i class="fa-solid fa-rotate" :class="loading ? 'fa-spin' : ''"></i>
                    Lancer une verification globale
                </button>
            </form>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mt-5">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Score global</p>
                <p class="text-2xl font-bold {{ $dataHealth['score'] >= 95 ? 'text-emerald-600' : ($dataHealth['score'] >= 80 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $dataHealth['score'] }}%
                </p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">TVA</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($dataHealth['tva_anomalies'], 0, ',', ' ') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Sur-payees</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($dataHealth['overpaid_invoices'], 0, ',', ' ') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Paiements incoherents</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($dataHealth['payment_mismatches'], 0, ',', ' ') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Ecart detecte</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($dataHealth['total_detected_delta'], 2, ',', ' ') }} DA</p>
            </div>
        </div>
    </div>

    {{-- ── Graphiques ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">

        {{-- Évolution paiements — 12 mois --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Évolution des encaissements
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">12 derniers mois</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Montant (DA)</span>
                </div>
            </div>
            <div class="h-56">
                <canvas id="chartPaiements"
                        data-labels='@json($moisLabels)'
                        data-amounts='@json($moisAmounts)'></canvas>
            </div>
        </div>

        {{-- Donut statut factures --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 p-6 shadow-sm">
            <div class="mb-6">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Statut des factures
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Répartition globale</p>
            </div>
            <div class="h-44 relative">
                <canvas id="chartDonut"
                        data-paid="{{ $paidInvoices }}"
                        data-unpaid="{{ $unpaidInvoices }}"
                        data-canceled="{{ $canceledInvoices }}"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-gray-600 dark:text-gray-400">Payées</span>
                    </span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 tabular-nums">
                        {{ number_format($paidInvoices, 0, ',', ' ') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-400"></span>
                        <span class="text-gray-600 dark:text-gray-400">Impayées</span>
                    </span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 tabular-nums">
                        {{ number_format($unpaidInvoices, 0, ',', ' ') }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
                        <span class="text-gray-600 dark:text-gray-400">Annulées</span>
                    </span>
                    <span class="font-semibold text-gray-800 dark:text-gray-200 tabular-nums">
                        {{ number_format($canceledInvoices, 0, ',', ' ') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Top Débiteurs + Payeurs ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">

        {{-- Top débiteurs --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                        dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                    Top 5 Débiteurs
                </h3>
                <a href="{{ route('admin.clients.index') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    Voir tout →
                </a>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @forelse($topDebiteurs as $i => $client)
                @php
                    $montant = $client->factures_sum_reste_a_payer ?? 0;
                    $maxMontant = $topDebiteurs->first()->factures_sum_reste_a_payer ?? 1;
                    $pct = $maxMontant > 0 ? ($montant / $maxMontant) * 100 : 0;
                @endphp
                <div class="px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold
                                     {{ $i === 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.clients.show', $client) }}"
                               class="text-sm font-medium text-gray-800 dark:text-gray-200
                                      hover:text-blue-600 dark:hover:text-blue-400 truncate block">
                                {{ $client->name }}
                            </a>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1">
                                    <div class="h-1 rounded-full bg-red-400"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-red-600 dark:text-red-400
                                             tabular-nums whitespace-nowrap">
                                    {{ number_format($montant, 0, ',', ' ') }} DA
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <i class="fa-solid fa-circle-check text-3xl text-emerald-300 mb-2 block"></i>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Aucun débiteur</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Top payeurs --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                        dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-star text-amber-500"></i>
                    Top 5 Payeurs
                </h3>
                <a href="{{ route('admin.clients.index') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    Voir tout →
                </a>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-700/50">
                @forelse($topPayeurs as $i => $client)
                @php
                    $montant = $client->montant_paye_total ?? 0;
                    $maxMontant = $topPayeurs->first()->montant_paye_total ?? 1;
                    $pct = $maxMontant > 0 ? ($montant / $maxMontant) * 100 : 0;
                @endphp
                <div class="px-6 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold
                                     {{ $i === 0 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-500' }}">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.clients.show', $client) }}"
                               class="text-sm font-medium text-gray-800 dark:text-gray-200
                                      hover:text-blue-600 dark:hover:text-blue-400 truncate block">
                                {{ $client->name }}
                            </a>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1">
                                    <div class="h-1 rounded-full bg-emerald-400"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400
                                             tabular-nums whitespace-nowrap">
                                    {{ number_format($montant, 0, ',', ' ') }} DA
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center">
                    <i class="fa-solid fa-inbox text-3xl text-gray-200 mb-2 block"></i>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Aucun paiement</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Activité récente ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">

        {{-- Factures récentes --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                    dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                        dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-blue-500"></i>
                    Factures Récentes
                </h3>
                <a href="{{ route('admin.factures.index') }}"
                   class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    Voir tout →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr class="text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">
                            <th class="px-5 py-2.5 text-left">N° Facture</th>
                            <th class="px-5 py-2.5 text-left">Client</th>
                            <th class="px-5 py-2.5 text-left hidden sm:table-cell">Date</th>
                            <th class="px-5 py-2.5 text-right">Montant TTC</th>
                            <th class="px-5 py-2.5 text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                        @forelse($recentInvoices as $inv)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.factures.show', $inv) }}"
                                   class="font-mono font-semibold text-blue-600 dark:text-blue-400
                                          hover:underline">
                                    {{ $inv->numero_facture }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300 max-w-[160px] truncate">
                                {{ $inv->client?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400 tabular-nums hidden sm:table-cell">
                                {{ $inv->date_facture?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-800
                                       dark:text-gray-200 tabular-nums">
                                {{ number_format($inv->total_ttc, 0, ',', ' ') }} DA
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($inv->reste_a_payer <= 0)
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700
                                                 dark:bg-emerald-900/40 dark:text-emerald-300
                                                 px-2 py-0.5 rounded-full text-[10px] font-semibold">
                                        <i class="fa-solid fa-circle-check text-[8px]"></i> Payée
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-orange-100 text-orange-700
                                                 dark:bg-orange-900/40 dark:text-orange-300
                                                 px-2 py-0.5 rounded-full text-[10px] font-semibold">
                                        <i class="fa-solid fa-clock text-[8px]"></i> Impayée
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                                Aucune facture récente.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panneau droite : utilisateurs + imports --}}
        <div class="flex flex-col gap-5">

            {{-- Stats utilisateurs --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 shadow-sm p-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-users text-purple-500"></i> Utilisateurs
                </h3>
                <div class="space-y-3">
                    @foreach([
                        ['label' => 'Total', 'value' => $totalUsers, 'color' => 'text-gray-800 dark:text-gray-200'],
                        ['label' => 'Administrateurs', 'value' => $totalAdmins, 'color' => 'text-blue-600'],
                        ['label' => 'Clients', 'value' => $totalClientUsers, 'color' => 'text-emerald-600'],
                        ['label' => 'Clients enregistrés', 'value' => $totalClients, 'color' => 'text-purple-600'],
                    ] as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                        <span class="text-sm font-bold {{ $item['color'] }} tabular-nums">
                            {{ number_format($item['value'], 0, ',', ' ') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Derniers imports --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                        dark:border-gray-700 shadow-sm p-5 flex-1">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-upload text-teal-500"></i> Derniers Imports
                    </h3>
                    <a href="{{ route('admin.imports.index') }}"
                       class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                        Voir tout →
                    </a>
                </div>
                <div class="space-y-2.5">
                    @forelse($recentImports as $imp)
                    @php
                        $typeColors = [
                            'factures'           => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'prestations'        => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300',
                            'paiements'          => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'factures_payees'    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'prestations_payees' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                        ];
                        $statusColors = [
                            'completed'  => 'text-emerald-600',
                            'processing' => 'text-blue-500',
                            'failed'     => 'text-red-500',
                            'pending'    => 'text-gray-400',
                        ];
                        $statusIcons = [
                            'completed'  => 'fa-circle-check',
                            'processing' => 'fa-spinner fa-spin',
                            'failed'     => 'fa-circle-xmark',
                            'pending'    => 'fa-clock',
                        ];
                    @endphp
                    <div class="flex items-center gap-2.5">
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold flex-shrink-0
                                     {{ $typeColors[$imp->type] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ Str::limit($imp->type, 10) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                {{ $imp->original_filename }}
                            </p>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 tabular-nums">
                                {{ $imp->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <i class="fa-solid {{ $statusIcons[$imp->status] ?? 'fa-question' }}
                                  text-xs {{ $statusColors[$imp->status] ?? 'text-gray-400' }}
                                  flex-shrink-0"></i>
                    </div>
                    @empty
                    <p class="text-xs text-gray-400 dark:text-gray-500 text-center py-4">
                        Aucun import récent.
                    </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Paiements récents ────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200
                dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100
                    dark:border-gray-700">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-coins text-emerald-500"></i>
                Paiements Récents
            </h3>
            <a href="{{ route('admin.paiements.index') }}"
               class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                Voir tout →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">
                        <th class="px-5 py-2.5 text-left">N° Facture</th>
                        <th class="px-5 py-2.5 text-left hidden sm:table-cell">Client</th>
                        <th class="px-5 py-2.5 text-left hidden md:table-cell">N° Reçu</th>
                        <th class="px-5 py-2.5 text-left hidden md:table-cell">Banque</th>
                        <th class="px-5 py-2.5 text-right">Montant</th>
                        <th class="px-5 py-2.5 text-right hidden sm:table-cell">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @forelse($recentPayments as $pay)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                        <td class="px-5 py-3">
                            <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">
                                {{ $pay->facture?->numero_facture ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400 max-w-[140px] truncate hidden sm:table-cell">
                            {{ $pay->facture?->client?->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400 tabular-nums hidden md:table-cell">
                            {{ $pay->recu ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400 hidden md:table-cell">
                            {{ $pay->banque ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                + {{ number_format($pay->montant, 0, ',', ' ') }} DA
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-gray-400 dark:text-gray-500 tabular-nums hidden sm:table-cell">
                            {{ $pay->date_paiement ?? '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                            Aucun paiement récent.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const isDark = document.documentElement.classList.contains('dark');
    const gridColor  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const textColor  = isDark ? '#9ca3af' : '#6b7280';
    const tooltipBg  = isDark ? '#1f2937' : '#ffffff';
    const tooltipTxt = isDark ? '#f3f4f6' : '#111827';

    // ── Graphique ligne — encaissements 12 mois ───────────────────────────
    const canvasPaiements = document.getElementById('chartPaiements');
    if (canvasPaiements) {
        const labels  = JSON.parse(canvasPaiements.dataset.labels);
        const amounts = JSON.parse(canvasPaiements.dataset.amounts);

        new Chart(canvasPaiements, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Encaissements (DA)',
                    data: amounts,
                    borderColor: '#3b82f6',
                    backgroundColor: (ctx) => {
                        const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                        gradient.addColorStop(0, 'rgba(59,130,246,0.25)');
                        gradient.addColorStop(1, 'rgba(59,130,246,0)');
                        return gradient;
                    },
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: isDark ? '#1f2937' : '#ffffff',
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
                        titleColor: tooltipTxt,
                        bodyColor: textColor,
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 12,
                        callbacks: {
                            label: (ctx) => ' ' + ctx.parsed.y.toLocaleString('fr-DZ') + ' DA',
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor, drawBorder: false },
                        ticks: { color: textColor, font: { size: 11 } },
                    },
                    y: {
                        grid: { color: gridColor, drawBorder: false },
                        ticks: {
                            color: textColor,
                            font: { size: 11 },
                            callback: (v) => {
                                if (v >= 1_000_000) return (v/1_000_000).toFixed(1) + 'M';
                                if (v >= 1_000)     return (v/1_000).toFixed(0) + 'k';
                                return v;
                            }
                        },
                    }
                }
            }
        });
    }

    // ── Donut — statut factures ───────────────────────────────────────────
    const canvasDonut = document.getElementById('chartDonut');
    if (canvasDonut) {
        const paid     = parseInt(canvasDonut.dataset.paid     || 0);
        const unpaid   = parseInt(canvasDonut.dataset.unpaid   || 0);
        const canceled = parseInt(canvasDonut.dataset.canceled || 0);

        new Chart(canvasDonut, {
            type: 'doughnut',
            data: {
                labels: ['Payées', 'Impayées', 'Annulées'],
                datasets: [{
                    data: [paid, unpaid, canceled],
                    backgroundColor: ['#10b981', '#fb923c', '#d1d5db'],
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: tooltipBg,
                        titleColor: tooltipTxt,
                        bodyColor: textColor,
                        borderColor: isDark ? '#374151' : '#e5e7eb',
                        borderWidth: 1,
                        padding: 10,
                        callbacks: {
                            label: (ctx) => ' ' + ctx.parsed.toLocaleString('fr-DZ') + ' factures',
                        }
                    }
                }
            }
        });
    }
});
</script>


@endsection
