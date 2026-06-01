{{-- // VIEW: client.factures.index --}}
{{-- // ROLE: client --}}
{{-- // FILTERS: search, statut, period, date range, amount range, sort, direction, per_page --}}
@php
    $pageTitle = 'Mes factures';
    $filterKeys = ['search', 'numero', 'statut', 'period', 'date_from', 'date_to', 'montant_min', 'montant_max'];
    $activeFilters = collect($filterKeys)->filter(fn ($key) => request()->filled($key))->count()
        + ((int) request('per_page', 25) !== 25 ? 1 : 0);

    $sortUrl = function (string $column) use ($sort, $direction) {
        $query = request()->except(['page', 'dir']);
        $query['sort'] = $column;
        $query['direction'] = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('client.factures.index', $query);
    };

    $sortIcon = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down';
    $sortClass = fn (string $column) => $sort === $column ? 'text-primary-700 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400';
    $ariaSort = fn (string $column) => $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none';
    $clearFilterUrl = fn (string $key) => route('client.factures.index', request()->except([$key, 'page']));
    $periodLabels = ['today' => 'Aujourd’hui', 'week' => 'Cette semaine', 'month' => 'Ce mois', 'custom' => 'Personnalisée'];

    $activeFilterBadges = collect([
        request()->filled('search') ? ['key' => 'search', 'label' => 'Recherche : '.request('search')] : null,
        request()->filled('numero') ? ['key' => 'numero', 'label' => 'Numéro : '.request('numero')] : null,
        request()->filled('statut') ? ['key' => 'statut', 'label' => 'Statut : '.($statusOptions[request('statut')] ?? request('statut'))] : null,
        request()->filled('period') ? ['key' => 'period', 'label' => 'Période : '.($periodLabels[request('period')] ?? request('period'))] : null,
        request()->filled('date_from') ? ['key' => 'date_from', 'label' => 'Depuis : '.\Illuminate\Support\Carbon::parse(request('date_from'))->format('d/m/Y')] : null,
        request()->filled('date_to') ? ['key' => 'date_to', 'label' => 'Jusqu’au : '.\Illuminate\Support\Carbon::parse(request('date_to'))->format('d/m/Y')] : null,
        request()->filled('montant_min') ? ['key' => 'montant_min', 'label' => 'Min. : '.number_format((float) request('montant_min'), 0, ',', ' ').' DA'] : null,
        request()->filled('montant_max') ? ['key' => 'montant_max', 'label' => 'Max. : '.number_format((float) request('montant_max'), 0, ',', ' ').' DA'] : null,
    ])->filter();

    $statusFor = function ($facture) {
        if ($facture->annuler) {
            return 'annulee';
        }

        if ((float) $facture->reste_a_payer <= 0) {
            return 'payee';
        }

        if ($facture->date_echeance && $facture->date_echeance->lt(today())) {
            return 'en_retard';
        }

        return 'impayee';
    };
@endphp

@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div
    x-data="{
        filtering: false,
        advanced: @js($activeFilters > 0),
        search: @js(request('search', request('numero', ''))),
        apply() {
            this.filtering = true;
            this.$refs.filters.requestSubmit ? this.$refs.filters.requestSubmit() : this.$refs.filters.submit();
        }
    }"
    class="space-y-6"
>
    <x-page-header
        title="Mes factures"
        :subtitle="$factures->total().' facture(s) disponible(s)'"
        :breadcrumbs="[['label' => 'Client'], ['label' => 'Factures']]"
    />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Factures" :value="number_format((int) ($stats->total_count ?? 0), 0, ',', ' ')" icon="file-text" color="primary" />
        <x-stat-card title="Total TTC" :value="number_format((float) ($stats->total_ttc ?? 0), 0, ',', ' ') . ' DA'" icon="coins" color="success" />
        <x-stat-card title="Reste à payer" :value="number_format((float) ($stats->reste_total ?? 0), 0, ',', ' ') . ' DA'" icon="circle-alert" color="danger" />
        <x-stat-card title="En retard" :value="number_format((int) ($stats->en_retard ?? 0), 0, ',', ' ')" icon="clock" color="warning" />
    </section>

    <form x-ref="filters" method="GET" action="{{ route('client.factures.index') }}" class="ui-card p-4" @submit="filtering = true">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <x-search-input
                    name="search"
                    placeholder="N° facture ou désignation..."
                    :value="request('search', request('numero'))"
                    x-model.debounce.500ms="search"
                    @change="apply()"
                    @keydown.enter.prevent="apply()"
                />
            </div>

            <div class="lg:col-span-3">
                <label for="statut" class="sr-only">Statut</label>
                <select id="statut" name="statut" class="ui-input min-h-11" @change="apply()">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('statut') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label for="period" class="sr-only">Période</label>
                <select id="period" name="period" class="ui-input min-h-11" @change="apply()">
                    <option value="">Toute période</option>
                    <option value="today" @selected(request('period') === 'today')>Aujourd’hui</option>
                    <option value="week" @selected(request('period') === 'week')>Cette semaine</option>
                    <option value="month" @selected(request('period') === 'month')>Ce mois</option>
                    <option value="custom" @selected(request('period') === 'custom')>Personnalisée</option>
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
                    <label for="montant_min" class="ui-label mb-1">Montant min.</label>
                    <input id="montant_min" type="number" min="0" step="0.01" name="montant_min" value="{{ request('montant_min', request('amount_min')) }}" class="ui-input" @change="apply()">
                </div>

                <div class="lg:col-span-2">
                    <label for="montant_max" class="ui-label mb-1">Montant max.</label>
                    <input id="montant_max" type="number" min="0" step="0.01" name="montant_max" value="{{ request('montant_max', request('amount_max')) }}" class="ui-input" @change="apply()">
                </div>

                <div class="lg:col-span-2">
                    <label for="per_page" class="ui-label mb-1">Lignes</label>
                    <select id="per_page" name="per_page" class="ui-input min-h-11" @change="apply()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} / page</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:col-span-2">
                    @if($activeFilters)
                        <a href="{{ route('client.factures.index') }}" class="ui-btn-secondary min-h-11 w-full">
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
        <a href="{{ route('client.factures.export.excel', request()->query()) }}" class="ui-btn-secondary min-h-11" aria-label="Exporter les factures filtrées en Excel">
            <i data-lucide="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>
            Excel
        </a>
        <a href="{{ route('client.factures.export.pdf', request()->query()) }}" class="ui-btn-danger min-h-11" aria-label="Exporter les factures filtrées en PDF">
            <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
            PDF
        </a>
    </div>

    <div class="ui-card relative overflow-hidden">
        <div x-show="filtering" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-gray-900/75">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow dark:bg-gray-800 dark:text-gray-200">
                <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                Chargement...
            </span>
        </div>

        @if($factures->isEmpty())
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                    <i data-lucide="file-text" class="h-8 w-8" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Aucune facture</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">Aucune facture ne correspond aux filtres actifs.</p>
                @if($activeFilters)
                    <a href="{{ route('client.factures.index') }}" class="ui-btn-primary mt-6">
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
                            <th scope="col" aria-sort="{{ $ariaSort('numero_facture') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('numero_facture') }}" class="inline-flex items-center gap-1 {{ $sortClass('numero_facture') }}">
                                    N° facture
                                    <i data-lucide="{{ $sortIcon('numero_facture') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Désignation</th>
                            <th scope="col" aria-sort="{{ $ariaSort('date_facture') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('date_facture') }}" class="inline-flex items-center gap-1 {{ $sortClass('date_facture') }}">
                                    Date
                                    <i data-lucide="{{ $sortIcon('date_facture') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" aria-sort="{{ $ariaSort('total_ttc') }}" class="px-4 py-3 text-right text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('total_ttc') }}" class="inline-flex items-center justify-end gap-1 {{ $sortClass('total_ttc') }}">
                                    Total TTC
                                    <i data-lucide="{{ $sortIcon('total_ttc') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" aria-sort="{{ $ariaSort('reste_a_payer') }}" class="px-4 py-3 text-right text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('reste_a_payer') }}" class="inline-flex items-center justify-end gap-1 {{ $sortClass('reste_a_payer') }}">
                                    Reste
                                    <i data-lucide="{{ $sortIcon('reste_a_payer') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" aria-sort="{{ $ariaSort('statut') }}" class="px-4 py-3 text-left text-xs font-semibold uppercase">
                                <a href="{{ $sortUrl('statut') }}" class="inline-flex items-center gap-1 {{ $sortClass('statut') }}">
                                    Statut
                                    <i data-lucide="{{ $sortIcon('statut') }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($factures as $facture)
                            @php
                                $status = $statusFor($facture);
                            @endphp
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-4">
                                    <a href="{{ route('client.factures.show', $facture) }}" class="font-semibold text-primary-700 hover:underline dark:text-primary-300">
                                        #{{ $facture->numero_facture }}
                                    </a>
                                </td>
                                <td class="max-w-xs px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="line-clamp-2">{{ $facture->pour ?? $facture->description ?? $facture->escale?->numero_escale ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $facture->date_facture?->format('d/m/Y') ?? '-' }}</td>
                                <td class="px-4 py-4 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format((float) $facture->total_ttc, 2, ',', ' ') }} DA</td>
                                <td class="px-4 py-4 text-right font-semibold tabular-nums {{ (float) $facture->reste_a_payer > 0 ? 'text-danger-700 dark:text-danger-300' : 'text-success-700 dark:text-success-300' }}">{{ number_format((float) $facture->reste_a_payer, 2, ',', ' ') }} DA</td>
                                <td class="px-4 py-4"><x-badge :status="$status" /></td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('client.factures.show', $facture) }}" class="ui-icon-btn" aria-label="Voir la facture {{ $facture->numero_facture }}">
                                            <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                                        </a>
                                        @if(! $facture->annuler)
                                            <a href="{{ route('client.invoices.facture.print', $facture) }}" target="_blank" class="ui-icon-btn" aria-label="Télécharger la facture {{ $facture->numero_facture }}">
                                                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                                            </a>
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
                        $status = $statusFor($facture);
                    @endphp
                    <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('client.factures.show', $facture) }}" class="font-semibold text-primary-700 hover:underline dark:text-primary-300">
                                    #{{ $facture->numero_facture }}
                                </a>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $facture->date_facture?->format('d/m/Y') ?? '-' }}</p>
                            </div>
                            <x-badge :status="$status" />
                        </div>

                        <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">{{ $facture->pour ?? $facture->description ?? $facture->escale?->numero_escale ?? '-' }}</p>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Total TTC</dt>
                                <dd class="font-semibold tabular-nums">{{ number_format((float) $facture->total_ttc, 2, ',', ' ') }} DA</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Reste à payer</dt>
                                <dd class="font-semibold tabular-nums">{{ number_format((float) $facture->reste_a_payer, 2, ',', ' ') }} DA</dd>
                            </div>
                        </dl>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <a href="{{ route('client.factures.show', $facture) }}" class="ui-btn-secondary">Détail</a>
                            @if(! $facture->annuler)
                                <a href="{{ route('client.invoices.facture.print', $facture) }}" target="_blank" class="ui-btn-secondary">PDF</a>
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
@endsection
