{{-- // VIEW: client.paiements.index --}}
{{-- // ROLE: client --}}
{{-- // FILTERS: search, banque, mode_paiement, date range, sort, direction, per_page --}}
@php
    $pageTitle = 'Mes paiements';
    $filterKeys = ['search', 'banque', 'mode_paiement', 'date_from', 'date_to'];
    $activeFilters = collect($filterKeys)->filter(fn ($key) => request()->filled($key))->count()
        + ((int) request('per_page', 25) !== 25 ? 1 : 0);

    $sortUrl = function (string $column) use ($sort, $direction) {
        $query = request()->except(['page', 'dir']);
        $query['sort'] = $column;
        $query['direction'] = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('client.paiements.index', $query);
    };

    $sortIcon = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
    $sortClass = fn (string $column) => $sort === $column ? 'text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
    $clearFilterUrl = fn (string $key) => route('client.paiements.index', request()->except([$key, 'page']));

    $activeFilterBadges = collect([
        request()->filled('search') ? ['key' => 'search', 'label' => 'Recherche : '.request('search')] : null,
        request()->filled('banque') ? ['key' => 'banque', 'label' => 'Banque : '.request('banque')] : null,
        request()->filled('mode_paiement') ? ['key' => 'mode_paiement', 'label' => 'Mode : '.($modeLabels[(int) request('mode_paiement')] ?? request('mode_paiement'))] : null,
        request()->filled('date_from') ? ['key' => 'date_from', 'label' => 'Depuis : '.\Illuminate\Support\Carbon::parse(request('date_from'))->format('d/m/Y')] : null,
        request()->filled('date_to') ? ['key' => 'date_to', 'label' => 'Jusqu’au : '.\Illuminate\Support\Carbon::parse(request('date_to'))->format('d/m/Y')] : null,
    ])->filter();
@endphp

@extends('clients.layouts.app')
@section('title', $pageTitle)

@section('content')
<div
    x-data="{
        filtering: false,
        advanced: @js($activeFilters > 0),
        search: @js(request('search', '')),
        apply() {
            this.filtering = true;
            this.$refs.filters.requestSubmit ? this.$refs.filters.requestSubmit() : this.$refs.filters.submit();
        }
    }"
    class="space-y-6"
>
    <x-page-header
        title="Mes paiements"
        :subtitle="$paiementGroups->total().' groupe(s) de paiement'"
        :breadcrumbs="[['label' => 'Client'], ['label' => 'Paiements']]"
    />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Paiements" :value="number_format((int) ($stats->total_count ?? 0), 0, ',', ' ')" icon="credit-card" color="primary" />
        <x-stat-card title="Montant total" :value="number_format((float) ($stats->total_montant ?? 0), 0, ',', ' ') . ' DA'" icon="coins" color="success" />
        <x-stat-card title="Chèques" :value="number_format((int) ($stats->total_cheques ?? 0), 0, ',', ' ')" icon="file-text" color="info" />
        <x-stat-card title="Filtres actifs" :value="$activeFilters" icon="sliders-horizontal" color="warning" />
    </section>

    <form x-ref="filters" method="GET" action="{{ route('client.paiements.index') }}" class="ui-card p-4" @submit="filtering = true">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-search-input
                    name="search"
                    placeholder="Reçu, chèque ou facture..."
                    :value="request('search')"
                    x-model.debounce.500ms="search"
                    @change="apply()"
                    @keydown.enter.prevent="apply()"
                />
            </div>

            <div class="lg:col-span-3">
                <label for="banque" class="sr-only">Banque</label>
                <select id="banque" name="banque" class="ui-input min-h-11" @change="apply()">
                    <option value="">Toutes les banques</option>
                    @foreach($banques as $banque)
                        <option value="{{ $banque }}" @selected(request('banque') === $banque)>{{ $banque }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="mode_paiement" class="sr-only">Mode de paiement</label>
                <select id="mode_paiement" name="mode_paiement" class="ui-input min-h-11" @change="apply()">
                    <option value="">Tous les modes</option>
                    @foreach($modeLabels as $mode => $label)
                        <option value="{{ $mode }}" @selected((string) request('mode_paiement') === (string) $mode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2 lg:col-span-3">
                <button type="submit" class="ui-btn-primary min-h-11">
                    <i data-lucide="search" class="h-4 w-4" aria-hidden="true"></i>
                    Filtrer
                </button>
                <button type="button" class="ui-btn-secondary relative min-h-11" @click="advanced = ! advanced" :aria-expanded="advanced.toString()">
                    <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
                    Options
                    @if($activeFilters)
                        <span class="absolute -right-1 -top-1 rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] text-white">{{ $activeFilters }}</span>
                    @endif
                </button>
            </div>
        </div>

        <div x-show="advanced" x-cloak class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
                <x-date-range-picker class="lg:col-span-4" @change="apply()" />

                <div class="lg:col-span-2">
                    <label for="per_page" class="ui-label mb-1">Lignes</label>
                    <select id="per_page" name="per_page" class="ui-input min-h-11" @change="apply()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:col-span-3">
                    @if($activeFilters)
                        <a href="{{ route('client.paiements.index') }}" class="ui-btn-secondary min-h-11 w-full">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            Réinitialiser
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($activeFilterBadges->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-200 pt-4 dark:border-gray-700" aria-label="Filtres actifs">
                @foreach($activeFilterBadges as $filter)
                    <a href="{{ $clearFilterUrl($filter['key']) }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-600/20 transition hover:bg-primary-100 focus:outline-none focus:ring-2 focus:ring-primary-600 dark:bg-primary-900/30 dark:text-primary-200">
                        {{ $filter['label'] }}
                        <i data-lucide="x" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        @endif
    </form>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('client.paiements.export.excel', request()->query()) }}" class="ui-btn-secondary min-h-11" aria-label="Exporter les paiements filtrés en Excel">
            <i data-lucide="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>
            Excel
        </a>
        <a href="{{ route('client.paiements.export.pdf', request()->query()) }}" class="ui-btn-danger min-h-11" aria-label="Exporter les paiements filtrés en PDF">
            <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
            PDF
        </a>
        <a href="{{ route('client.paiements.print', request()->query()) }}" target="_blank" class="ui-btn-secondary min-h-11" aria-label="Imprimer les paiements filtrés">
            <i data-lucide="printer" class="h-4 w-4" aria-hidden="true"></i>
            Imprimer
        </a>
    </div>

    <div class="ui-card relative overflow-hidden">
        <div x-show="filtering" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-gray-900/75">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow dark:bg-gray-800 dark:text-gray-200">
                <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                Chargement...
            </span>
        </div>

        @if($paiementGroups->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                    <i data-lucide="credit-card" class="h-8 w-8" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Aucun paiement</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">Aucun paiement ne correspond aux filtres actifs.</p>
                @if($activeFilters)
                    <a href="{{ route('client.paiements.index') }}" class="ui-btn-primary mt-6">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                        Réinitialiser les filtres
                    </a>
                @endif
            </div>
        @else
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th scope="col" aria-sort="{{ $ariaSort('numero_cheque') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('numero_cheque') }}" class="inline-flex items-center gap-1 {{ $sortClass('numero_cheque') }}" aria-label="Trier par numéro de chèque">
                                    N° chèque
                                    <i data-lucide="{{ $sortIcon('numero_cheque') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Factures associées</th>
                            <th scope="col" aria-sort="{{ $ariaSort('date_paiement') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('date_paiement') }}" class="inline-flex items-center gap-1 {{ $sortClass('date_paiement') }}" aria-label="Trier par date de paiement">
                                    Date récente
                                    <i data-lucide="{{ $sortIcon('date_paiement') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" aria-sort="{{ $ariaSort('montant') }}" class="px-4 py-3 text-right text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('montant') }}" class="inline-flex items-center justify-end gap-1 {{ $sortClass('montant') }}" aria-label="Trier par montant total">
                                    Total
                                    <i data-lucide="{{ $sortIcon('montant') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" aria-sort="{{ $ariaSort('banque') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('banque') }}" class="inline-flex items-center gap-1 {{ $sortClass('banque') }}" aria-label="Trier par banque">
                                    Banque
                                    <i data-lucide="{{ $sortIcon('banque') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Mode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($paiementGroups as $group)
                            @php
                                $groupId = 'paiement-group-'.md5((string) $group->key);
                            @endphp
                            <tr x-data="{ open: false }" class="align-top transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <button
                                            type="button"
                                            class="ui-icon-btn min-h-11 min-w-11"
                                            @click="open = ! open"
                                            :aria-expanded="open.toString()"
                                            aria-controls="{{ $groupId }}"
                                            aria-label="Afficher ou masquer les factures du groupe {{ $group->is_direct ? 'paiement direct' : 'chèque '.$group->numero_cheque }}"
                                        >
                                            <i x-show="!open" data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                                            <i x-show="open" x-cloak data-lucide="chevron-down" class="h-4 w-4" aria-hidden="true"></i>
                                        </button>

                                        <div class="min-w-0">
                                            @if($group->is_direct)
                                                <x-badge status="warning" label="Paiement direct" />
                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Reçu {{ $group->recu ?? '-' }}</p>
                                            @else
                                                <p class="font-semibold text-primary-700 dark:text-primary-300">#{{ $group->numero_cheque }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $group->factures_count }} facture(s), {{ $group->paiements_count }} paiement(s)
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($group->paiements->take(4) as $paiement)
                                            @if($paiement->facture)
                                                <a href="{{ route('client.factures.show', $paiement->facture) }}" class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-primary-50 hover:text-primary-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-primary-900/30 dark:hover:text-primary-200">
                                                    #{{ $paiement->facture->numero_facture }}
                                                </a>
                                            @endif
                                        @endforeach
                                        @if($group->paiements->count() > 4)
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">+{{ $group->paiements->count() - 4 }}</span>
                                        @endif
                                    </div>

                                    <div id="{{ $groupId }}" x-show="open" x-cloak x-transition class="mt-3 space-y-2 border-l-2 border-primary-500 pl-3">
                                        @foreach($group->paiements as $paiement)
                                            <div class="rounded-md border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0">
                                                        @if($paiement->facture)
                                                            <a href="{{ route('client.factures.show', $paiement->facture) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">
                                                                Facture #{{ $paiement->facture->numero_facture }}
                                                            </a>
                                                        @else
                                                            <span class="font-medium text-gray-700 dark:text-gray-300">Facture -</span>
                                                        @endif
                                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                            Reçu {{ $paiement->recu ?? '-' }} - {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="shrink-0 text-sm font-semibold tabular-nums text-success-700 dark:text-success-300">
                                                        {{ number_format((float) $paiement->montant, 2, ',', ' ') }} DA
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $group->date_paiement ? \Carbon\Carbon::parse($group->date_paiement)->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-4 py-4 text-right font-semibold tabular-nums text-success-700 dark:text-success-300">
                                    {{ number_format((float) $group->total_montant, 2, ',', ' ') }} DA
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $group->banque ?? '-' }}</td>
                                <td class="px-4 py-4"><x-badge status="regle" :label="$modeLabels[(int) $group->mode_paiement] ?? 'Réglé'" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @foreach($paiementGroups as $group)
                    @php
                        $groupId = 'paiement-card-'.md5((string) $group->key);
                    @endphp
                    <article x-data="{ open: false }" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                @if($group->is_direct)
                                    <x-badge status="warning" label="Paiement direct" />
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Reçu {{ $group->recu ?? '-' }}</p>
                                @else
                                    <p class="font-semibold text-primary-700 dark:text-primary-300">Chèque #{{ $group->numero_cheque }}</p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $group->factures_count }} facture(s), {{ $group->paiements_count }} paiement(s)
                                    </p>
                                @endif
                            </div>
                            <p class="shrink-0 text-right font-semibold tabular-nums text-success-700 dark:text-success-300">
                                {{ number_format((float) $group->total_montant, 2, ',', ' ') }} DA
                            </p>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Date récente</dt>
                                <dd class="font-semibold">{{ $group->date_paiement ? \Carbon\Carbon::parse($group->date_paiement)->format('d/m/Y') : '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Banque</dt>
                                <dd class="font-semibold">{{ $group->banque ?? '-' }}</dd>
                            </div>
                        </dl>

                        <button
                            type="button"
                            class="ui-btn-secondary mt-4 min-h-11 w-full"
                            @click="open = ! open"
                            :aria-expanded="open.toString()"
                            aria-controls="{{ $groupId }}"
                        >
                            <i x-show="!open" data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                            <i x-show="open" x-cloak data-lucide="chevron-down" class="h-4 w-4" aria-hidden="true"></i>
                            <span x-text="open ? 'Masquer les factures' : 'Afficher les factures'"></span>
                        </button>

                        <div id="{{ $groupId }}" x-show="open" x-cloak x-transition class="mt-4 space-y-3 border-l-2 border-primary-500 pl-3">
                            @foreach($group->paiements as $paiement)
                                <div class="rounded-md border border-gray-200 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            @if($paiement->facture)
                                                <a href="{{ route('client.factures.show', $paiement->facture) }}" class="font-semibold text-primary-700 hover:underline dark:text-primary-300">
                                                    Facture #{{ $paiement->facture->numero_facture }}
                                                </a>
                                            @else
                                                <span class="font-semibold">Facture -</span>
                                            @endif
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Reçu {{ $paiement->recu ?? '-' }} - {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '-' }}
                                            </p>
                                        </div>
                                        <span class="shrink-0 font-semibold tabular-nums">{{ number_format((float) $paiement->montant, 2, ',', ' ') }} DA</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                    Affichage {{ $paiementGroups->firstItem() }}-{{ $paiementGroups->lastItem() }} sur {{ $paiementGroups->total() }} groupe(s) de paiement
                </div>
                {{ $paiementGroups->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
