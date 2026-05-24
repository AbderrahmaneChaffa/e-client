{{-- // VIEW: client.factures.index --}}
{{-- // ROLE: client --}}
{{-- // COMPONENTS: <x-page-header>, <x-search-input>, <x-date-range-picker>, <x-badge>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: search, status, period, date range, sort, per_page --}}
@php
    $pageTitle = 'Mes factures';
    $activeFilters = collect(['search', 'numero', 'statut', 'period', 'date_from', 'date_to', 'sort', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="{ state: 'data', filtering: false, advanced: false }" class="space-y-6">
    <x-page-header
        title="Mes factures"
        :subtitle="$factures->total().' facture(s) disponible(s)'"
        :breadcrumbs="[['label' => 'Client'], ['label' => 'Factures']]"
    />

    <div x-show="state === 'loading'" x-cloak><x-loading-skeleton rows="5" /></div>

    <div class="space-y-4">
        <form method="GET" action="{{ route('client.factures.index') }}" class="ui-card p-4" @submit="filtering = true">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4"><x-search-input name="search" placeholder="N facture ou description..." :value="request('search', request('numero'))" /></div>
                <div class="lg:col-span-3">
                    <select name="statut" class="ui-input" aria-label="Statut">
                        <option value="">Tous statuts</option>
                        <option value="paye" @selected(request('statut') === 'paye')>Payees</option>
                        <option value="impaye" @selected(request('statut') === 'impaye')>Impayees</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="period" class="ui-input" aria-label="Periode">
                        <option value="">Toute periode</option>
                        <option value="today" @selected(request('period') === 'today')>Aujourd'hui</option>
                        <option value="week" @selected(request('period') === 'week')>Cette semaine</option>
                        <option value="month" @selected(request('period') === 'month')>Ce mois</option>
                        <option value="custom" @selected(request('period') === 'custom')>Personnalise</option>
                    </select>
                </div>
                <div class="flex gap-2 lg:col-span-3">
                    <button type="submit" class="ui-btn-primary flex-1">Filtrer</button>
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
                        <label for="sort" class="ui-label mb-1">Tri</label>
                        <select id="sort" name="sort" class="ui-input">
                            <option value="date_facture" @selected(request('sort', 'date_facture') === 'date_facture')>Date</option>
                            <option value="numero_facture" @selected(request('sort') === 'numero_facture')>Numero</option>
                            <option value="total_ttc" @selected(request('sort') === 'total_ttc')>Montant</option>
                            <option value="reste_a_payer" @selected(request('sort') === 'reste_a_payer')>Reste a payer</option>
                        </select>
                    </div>
                    <div>
                        <label for="per_page" class="ui-label mb-1">Entrees</label>
                        <select id="per_page" name="per_page" class="ui-input">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    @if($activeFilters)
                        <a href="{{ route('client.factures.index') }}" class="ui-btn-secondary"><i data-lucide="rotate-ccw" class="h-4 w-4"></i>Reinitialiser</a>
                    @endif
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

            @if($factures->isEmpty())
                <div class="p-4"><x-empty-state icon="file-text" title="Aucune facture" message="Aucune facture ne correspond aux filtres." /></div>
            @else
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">N facture</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Resume</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Montant</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Statut</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($factures as $facture)
                                @php
                                    $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
                                @endphp
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-4"><a href="{{ route('client.factures.show', $facture) }}" class="font-semibold text-primary-600 dark:text-primary-300">#{{ $facture->numero_facture }}</a></td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $facture->pour ?? $facture->description ?? $facture->escale?->numero_escale ?? '-' }}</td>
                                    <td class="px-4 py-4 text-right font-semibold tabular-nums">{{ number_format($facture->total_ttc, 2, ',', ' ') }} DA</td>
                                    <td class="px-4 py-4"><x-badge :status="$status" /></td>
                                    <td class="px-4 py-4 text-sm">{{ optional($facture->date_facture)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('client.factures.show', $facture) }}" class="ui-icon-btn" aria-label="Voir"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                            @if(! $facture->annuler)
                                                <a href="{{ route('client.invoices.facture.print', $facture) }}" target="_blank" class="ui-icon-btn" aria-label="Telecharger facture"><i data-lucide="download" class="h-4 w-4"></i></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @foreach($factures as $facture)
                        @php
                            $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
                        @endphp
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('client.factures.show', $facture) }}" class="font-semibold text-primary-600 dark:text-primary-300">#{{ $facture->numero_facture }}</a>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ optional($facture->date_facture)->format('d/m/Y') }}</p>
                                </div>
                                <x-badge :status="$status" />
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Montant</span><p class="font-semibold">{{ number_format($facture->total_ttc, 0, ',', ' ') }} DA</p></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Reste</span><p class="font-semibold">{{ number_format($facture->reste_a_payer, 0, ',', ' ') }} DA</p></div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('client.factures.show', $facture) }}" class="ui-btn-secondary flex-1">Voir detail</a>
                                @if(! $facture->annuler)
                                    <a href="{{ route('client.invoices.facture.print', $facture) }}" target="_blank" class="ui-btn-secondary flex-1">Facture PDF</a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        Affichage {{ $factures->firstItem() }}-{{ $factures->lastItem() }} sur {{ $factures->total() }} factures
                    </div>
                    {{ $factures->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
