@php
    $pageTitle = 'Mes paiements';
    $activeFilters = collect(['search', 'banque', 'date_from', 'date_to', 'sort', 'per_page'])
        ->filter(fn($key) => request()->filled($key))->count();

    // ✅ Priorité aux variables passées par le controller
    $pageTotal = $totalMontant ?? ($paiements?->sum('montant') ?? 0);
    $displayCount = $paginatedGroups?->count() ?? ($paiements?->count() ?? 0);
    $totalCount = $totalPaiements ?? ($paiements?->count() ?? 0);
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
    <div x-data="{ state: 'data', filtering: false, advanced: false }" class="space-y-6">
        <x-page-header title="Mes paiements" :subtitle="$totalCount . ' paiement(s) retrouve(s)'" :breadcrumbs="[['label' => 'Client'], ['label' => 'Paiements']]" />

        {{-- Boutons d'export --}}
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('client.paiements.export.excel', request()->query()) }}" class="ui-btn-success"
                title="Exporter en Excel">
                <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Excel
            </a>
            <a href="{{ route('client.paiements.export.pdf', request()->query()) }}" class="ui-btn-danger"
                title="Exporter en PDF" target="_blank">
                <i data-lucide="file-text" class="h-4 w-4"></i> PDF
            </a>
            <button type="button" onclick="window.print()" class="ui-btn-secondary" title="Imprimer">
                <i data-lucide="printer" class="h-4 w-4"></i> Imprimer
            </button>
        </div>
        <div x-show="state === 'loading'" x-cloak><x-loading-skeleton rows="5" /></div>

        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <x-stat-card title="Paiements" :value="number_format($totalPaiements, 0, ',', ' ')" icon="credit-card"
                    color="primary" />
                <x-stat-card title="Total" :value="number_format($totalMontant, 0, ',', ' ') . ' DA'" icon="coins"
                    color="success" />
                <x-stat-card title="Chèques" :value="$totalCheques" icon="receipt" color="info" />
                <x-stat-card title="Filtres" :value="$activeFilters" icon="sliders-horizontal" color="warning" />
            </section>

            <form method="GET" action="{{ route('client.paiements.index') }}" class="ui-card p-4"
                @submit="filtering = true">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-4"><x-search-input name="search" placeholder="Recu, cheque, facture..." /></div>
                    <div class="lg:col-span-3"><input name="banque" value="{{ request('banque') }}" class="ui-input"
                            placeholder="Banque"></div>
                    <div class="lg:col-span-2">
                        <select name="per_page" class="ui-input" aria-label="Entrees">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 lg:col-span-3">
                        <button type="submit" class="ui-btn-primary flex-1">Filtrer</button>
                        <button type="button" class="ui-btn-secondary relative" @click="advanced = ! advanced">
                            <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
                            @if($activeFilters)
                                <span
                                    class="absolute -right-1 -top-1 rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] text-white">{{ $activeFilters }}</span>
                            @endif
                        </button>
                    </div>
                </div>

                <div x-show="advanced" x-cloak class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <x-date-range-picker />
                        <div>
                            <label for="sort" class="ui-label mb-1">Tri</label>
                            <select id="sort" name="sort" class="ui-input">
                                <option value="date_paiement" @selected(request('sort', 'date_paiement') === 'date_paiement')>
                                    Date</option>
                                <option value="montant" @selected(request('sort') === 'montant')>
                                    Montant</option>
                                <option value="recu" @selected(request('sort') === 'recu')>Recu
                                </option>
                            </select>
                        </div>
                        <div>
                            <label for="dir" class="ui-label mb-1">Direction</label>
                            <select id="dir" name="dir" class="ui-input">
                                <option value="desc" @selected(request('dir') !== 'asc')>
                                    Descendant</option>
                                <option value="asc" @selected(request('dir') === 'asc')>Ascendant
                                </option>
                            </select>
                        </div>
                    </div>
                    @if($activeFilters)
                        <div class="mt-4"><a href="{{ route('client.paiements.index') }}" class="ui-btn-secondary"><i
                                    data-lucide="rotate-ccw" class="h-4 w-4"></i>Reinitialiser</a></div>
                    @endif
                </div>
            </form>

            <div class="ui-card relative overflow-hidden">
                <div x-show="filtering" x-cloak
                    class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-gray-900/70">
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold shadow dark:bg-gray-800">
                        <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                        Chargement...
                    </span>
                </div>

                @if($paiements->isEmpty())
                    <div class="p-4"><x-empty-state icon="credit-card" title="Aucun paiement"
                            message="Aucun paiement ne correspond aux filtres." /></div>
                @else
                    <div class="hidden md:block">
                        {{-- Tableau Desktop : Groupé par numéro de chèque --}}
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/60">
                                    <tr>
                                        <th scope="col"
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                            N° Chèque</th>
                                        <th scope="col"
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                            Factures associées</th>
                                        <th scope="col"
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                            Banque</th>
                                        <th scope="col"
                                            class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">
                                            Total</th>
                                        <th scope="col"
                                            class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                                            Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($paginatedGroups as $chequeKey => $paiementsGroup)
                                        @php
                                            $isSansCheque = str_starts_with($chequeKey, 'sans_cheque_');
                                            $chequeNumber = $isSansCheque ? null : $chequeKey;
                                            $totalGroupe = $paiementsGroup->sum('montant');
                                            $premierPaiement = $paiementsGroup->first();
                                        @endphp
                                        <tr
                                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50 bg-primary-50/30 dark:bg-primary-900/20">
                                            <td class="px-4 py-4">
                                                @if($chequeNumber)
                                                    <span
                                                        class="font-bold text-primary-700 dark:text-primary-300">#{{ $chequeNumber }}</span>
                                                @else
                                                    <x-badge status="warning" label="Paiement direct" />
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="space-y-1">
                                                    @foreach($paiementsGroup as $p)
                                                        @if($p->facture)
                                                            <a href="{{ route('client.factures.show', $p->facture) }}"
                                                                class="block text-sm text-primary-600 hover:underline dark:text-primary-300">
                                                                #{{ $p->facture->numero_facture }}
                                                                <span class="text-gray-400">({{ number_format($p->montant, 0, ',', ' ') }}
                                                                    DA)</span>
                                                            </a>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-sm">
                                                {{ $premierPaiement->banque ?? '-' }}
                                            </td>
                                            <td class="px-4 py-4 text-right font-bold text-success-700 dark:text-success-300">
                                                {{ number_format($totalGroupe, 2, ',', ' ') }} DA
                                            </td>
                                            <td class="px-4 py-4 text-sm">
                                                {{ $premierPaiement->date_paiement ? \Carbon\Carbon::parse($premierPaiement->date_paiement)->format('d/m/Y') : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Version Mobile : Cartes groupées --}}
                        <div class="space-y-4 p-4 md:hidden">
                            @foreach($paginatedGroups as $chequeKey => $paiementsGroup)
                                @php
                                    $isSansCheque = str_starts_with($chequeKey, 'sans_cheque_');
                                    $chequeNumber = $isSansCheque ? null : $chequeKey;
                                    $totalGroupe = $paiementsGroup->sum('montant');
                                    $premierPaiement = $paiementsGroup->first();
                                @endphp
                                <article
                                    class="rounded-lg border border-primary-200 dark:border-primary-800 p-4 bg-primary-50/20 dark:bg-primary-900/10">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            @if($chequeNumber)
                                                <p class="font-bold text-primary-700 dark:text-primary-300">
                                                    Chèque #{{ $chequeNumber }}</p>
                                            @else
                                                <x-badge status="warning" label="Paiement direct" />
                                            @endif
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $paiementsGroup->count() }} facture(s) •
                                                {{ $premierPaiement->banque ?? 'Banque non spécifiée' }}
                                            </p>
                                        </div>
                                        <p class="font-bold text-success-700 dark:text-success-300">
                                            {{ number_format($totalGroupe, 0, ',', ' ') }} DA
                                        </p>
                                    </div>

                                    <div class="mt-3 space-y-2 border-t border-gray-200 dark:border-gray-700 pt-3">
                                        @foreach($paiementsGroup as $p)
                                            <div class="flex justify-between text-sm">
                                                <a href="{{ route('client.factures.show', $p->facture) }}"
                                                    class="text-primary-600 hover:underline dark:text-primary-300">
                                                    #{{ $p->facture?->numero_facture ?? 'N/A' }}
                                                </a>
                                                <span>{{ number_format($p->montant, 0, ',', ' ') }}
                                                    DA</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        Date :
                                        {{ $premierPaiement->date_paiement ? \Carbon\Carbon::parse($premierPaiement->date_paiement)->format('d/m/Y') : '-' }}
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-3 p-4 md:hidden">
                        @foreach($paiements as $paiement)
                            <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">Recu {{ $paiement->recu ?? '-' }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Facture
                                            #{{ $paiement->facture?->numero_facture ?? '-' }}</p>
                                    </div>
                                    <x-badge status="completed" label="Regle" />
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div><span class="text-gray-500 dark:text-gray-400">Montant</span>
                                        <p class="font-semibold">
                                            {{ number_format($paiement->montant, 0, ',', ' ') }} DA
                                        </p>
                                    </div>
                                    <div><span class="text-gray-500 dark:text-gray-400">Date</span>
                                        <p class="font-semibold">
                                            {{ $paiement->date_paiement ? \Illuminate\Support\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                        <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                            Affichage {{ $paginatedGroups->firstItem() }}-{{ $paginatedGroups->lastItem() }}
                            sur {{ $paginatedGroups->total() }} groupe(s) de paiement
                            ({{ $totalPaiements }} paiement(s) au total)
                        </div>
                        {{ $paginatedGroups->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection