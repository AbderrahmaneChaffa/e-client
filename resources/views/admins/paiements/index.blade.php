{{-- // VIEW: admin.paiements.index --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-stat-card>, <x-search-input>, <x-date-range-picker>, <x-badge>, <x-avatar>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: search, bank, date range, sort, per_page, export query params --}}
@php
    $pageTitle = 'Paiements';
    $activeFilters = collect(['search', 'banque', 'date_from', 'date_to', 'sort', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
    $pageTotal = $paiements->getCollection()->sum('montant');
    $banks = $paiements->getCollection()->pluck('banque')->filter()->unique()->sort()->values();
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="{ state: 'data', filtering: false, advanced: false }" class="space-y-6">
    <x-page-header
        title="Paiements"
        :subtitle="$paiements->total().' paiement(s) trouve(s)'"
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Paiements']]"
    />

    <div x-show="state === 'loading'" x-cloak>
        <x-loading-skeleton rows="5" />
    </div>

    <div class="space-y-6 transition-all duration-300">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card title="Paiements" :value="number_format($paiements->total(), 0, ',', ' ')" icon="credit-card" color="primary" />
            <x-stat-card title="Total page" :value="number_format($pageTotal, 0, ',', ' ').' DA'" icon="coins" color="success" />
            <x-stat-card title="Banques visibles" :value="number_format($banks->count(), 0, ',', ' ')" icon="landmark" color="info" />
        </section>

        <form method="GET" action="{{ route('admin.paiements.index') }}" class="ui-card p-4" @submit="filtering = true">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-search-input name="search" placeholder="Recu, cheque, facture, client..." />
                </div>
                <div class="lg:col-span-3">
                    <input name="banque" value="{{ request('banque') }}" class="ui-input" placeholder="Banque">
                </div>
                <div class="lg:col-span-2">
                    <select name="per_page" class="ui-input" aria-label="Entrees">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 lg:col-span-3">
                    <button type="submit" class="ui-btn-primary flex-1">
                        <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                        Filtrer
                    </button>
                    <button type="button" class="ui-btn-secondary relative" @click="advanced = ! advanced">
                        <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
                        @if($activeFilters)
                            <span class="absolute -right-1 -top-1 rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] text-white">{{ $activeFilters }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div x-show="advanced" x-cloak class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <x-date-range-picker />
                    <div>
                        <label class="ui-label mb-1" for="sort">Tri</label>
                        <select id="sort" name="sort" class="ui-input">
                            <option value="date_paiement" @selected(request('sort', 'date_paiement') === 'date_paiement')>Date</option>
                            <option value="montant" @selected(request('sort') === 'montant')>Montant</option>
                            <option value="recu" @selected(request('sort') === 'recu')>Recu</option>
                            <option value="banque" @selected(request('sort') === 'banque')>Banque</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="dir">Direction</label>
                        <select id="dir" name="dir" class="ui-input">
                            <option value="desc" @selected(request('dir') !== 'asc')>Descendant</option>
                            <option value="asc" @selected(request('dir') === 'asc')>Ascendant</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($activeFilters)
                        <a href="{{ route('admin.paiements.index') }}" class="ui-btn-secondary"><i data-lucide="rotate-ccw" class="h-4 w-4"></i>Reinitialiser</a>
                    @endif
                    <a href="{{ route('admin.paiements.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="ui-btn-secondary"><i data-lucide="file-down" class="h-4 w-4"></i>CSV</a>
                    <a href="{{ route('admin.paiements.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="ui-btn-secondary"><i data-lucide="file-text" class="h-4 w-4"></i>PDF</a>
                </div>
            </div>
        </form>

        <div class="ui-card relative overflow-hidden">
            <div x-show="filtering" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-gray-900/70">
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold shadow dark:bg-gray-800">
                    <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                    Chargement...
                </span>
            </div>

            @if($paiements->isEmpty())
                <div class="p-4">
                    <x-empty-state icon="credit-card" title="Aucun paiement" message="Aucun paiement ne correspond aux filtres selectionnes." />
                </div>
            @else
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Client</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Facture</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Recu</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Banque</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Montant</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($paiements as $paiement)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :name="$paiement->facture?->client?->name ?? 'Client'" size="sm" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $paiement->facture?->client?->name ?? '-' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $paiement->facture?->client?->code_client ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($paiement->facture)
                                            <a href="{{ route('admin.factures.show', $paiement->facture) }}" class="font-semibold text-primary-600 dark:text-primary-300">#{{ $paiement->facture->numero_facture }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm">{{ $paiement->recu ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm">{{ $paiement->banque ?? '-' }}</td>
                                    <td class="px-4 py-4 text-right font-semibold tabular-nums text-success-700 dark:text-success-300">{{ number_format($paiement->montant, 2, ',', ' ') }} DA</td>
                                    <td class="px-4 py-4 text-sm">{{ $paiement->date_paiement ? \Illuminate\Support\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @foreach($paiements as $paiement)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold">{{ $paiement->facture?->client?->name ?? '-' }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Recu {{ $paiement->recu ?? '-' }}</p>
                                </div>
                                <x-badge status="completed" label="Regle" />
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Montant</span><p class="font-semibold">{{ number_format($paiement->montant, 0, ',', ' ') }} DA</p></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Date</span><p class="font-semibold">{{ $paiement->date_paiement ? \Illuminate\Support\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}</p></div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        Affichage {{ $paiements->firstItem() }}-{{ $paiements->lastItem() }} sur {{ $paiements->total() }} paiements
                    </div>
                    {{ $paiements->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
