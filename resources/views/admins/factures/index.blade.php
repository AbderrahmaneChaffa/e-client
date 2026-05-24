{{-- // VIEW: admin.factures.index --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-stat-card>, <x-search-input>, <x-date-range-picker>, <x-badge>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: search, client, status, verification, date range, period, amount min/max, sort, per_page, export query params --}}
@php
    $pageTitle = 'Factures';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
@php
    $activeFilters = collect(['search', 'numero', 'client_id', 'statut', 'verification', 'date_from', 'date_to', 'period', 'amount_min', 'amount_max', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<div x-data="{ state: 'data', filtering: false, advanced: false }" class="space-y-6">
    <x-page-header
        title="Factures"
        :subtitle="$factures->total().' facture(s) trouvee(s)'"
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Factures']]"
    />

    <div x-show="state === 'loading'" x-cloak>
        <x-loading-skeleton rows="5" />
    </div>

    <div x-show="state === 'error'" x-cloak class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm font-medium">Impossible de charger les factures.</p>
            <button class="ui-btn-secondary" @click="state = 'data'">Reessayer</button>
        </div>
    </div>

    <div class="space-y-6 transition-all duration-300">
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Total TTC" :value="number_format($stats['total_ttc'] ?? 0, 0, ',', ' ').' DA'" icon="receipt-text" color="info" />
            <x-stat-card title="Reste a payer" :value="number_format($stats['reste_total'] ?? 0, 0, ',', ' ').' DA'" icon="badge-alert" color="danger" />
            <x-stat-card title="Factures payees" :value="number_format($stats['count_payees'] ?? 0, 0, ',', ' ')" icon="check-circle-2" color="success" />
            <x-stat-card title="Anomalies" :value="number_format($stats['count_anomalies'] ?? 0, 0, ',', ' ')" icon="triangle-alert" color="warning" />
        </section>

        <form method="GET" action="{{ route('admin.factures.index') }}" class="ui-card p-4" @submit="filtering = true">
            <div class="grid grid-cols-1 gap-3 xl:grid-cols-12">
                <div class="xl:col-span-3">
                    <x-search-input name="search" placeholder="N facture, client, code..." :value="request('search', request('numero'))" />
                </div>
                <div class="xl:col-span-3">
                    <select name="client_id" class="ui-input" aria-label="Client">
                        <option value="">Tous les clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" @selected(request('client_id') == $client->id)>{{ $client->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <select name="statut" class="ui-input" aria-label="Statut">
                        <option value="">Tous statuts</option>
                        <option value="paye" @selected(request('statut') === 'paye')>Payees</option>
                        <option value="impaye" @selected(request('statut') === 'impaye')>Impayees</option>
                        <option value="annulee" @selected(request('statut') === 'annulee')>Annulees</option>
                    </select>
                </div>
                <div class="xl:col-span-2">
                    <select name="period" class="ui-input" aria-label="Periode">
                        <option value="">Toute periode</option>
                        <option value="today" @selected(request('period') === 'today')>Aujourd'hui</option>
                        <option value="week" @selected(request('period') === 'week')>Cette semaine</option>
                        <option value="month" @selected(request('period') === 'month')>Ce mois</option>
                        <option value="custom" @selected(request('period') === 'custom')>Personnalise</option>
                    </select>
                </div>
                <div class="flex gap-2 xl:col-span-2">
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
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <x-date-range-picker />
                    <div>
                        <label class="ui-label mb-1" for="verification">Verification</label>
                        <select id="verification" name="verification" class="ui-input">
                            <option value="">Toutes</option>
                            <option value="anomalies" @selected(request('verification') === 'anomalies')>Avec anomalies</option>
                            <option value="critical" @selected(request('verification') === 'critical')>Critique</option>
                            <option value="warning" @selected(request('verification') === 'warning')>Avertissement</option>
                            <option value="ok" @selected(request('verification') === 'ok')>OK</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="sort_by">Tri</label>
                        <select id="sort_by" name="sort_by" class="ui-input">
                            <option value="date_desc" @selected(request('sort_by', 'date_desc') === 'date_desc')>Date recente</option>
                            <option value="date_asc" @selected(request('sort_by') === 'date_asc')>Date ancienne</option>
                            <option value="montant_desc" @selected(request('sort_by') === 'montant_desc')>Montant haut</option>
                            <option value="montant_asc" @selected(request('sort_by') === 'montant_asc')>Montant bas</option>
                            <option value="numero_asc" @selected(request('sort_by') === 'numero_asc')>Numero A-Z</option>
                            <option value="numero_desc" @selected(request('sort_by') === 'numero_desc')>Numero Z-A</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="per_page">Entrees</label>
                        <select id="per_page" name="per_page" class="ui-input">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="amount_min">Montant min</label>
                        <input id="amount_min" name="amount_min" type="number" min="0" step="0.01" value="{{ request('amount_min') }}" class="ui-input">
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="amount_max">Montant max</label>
                        <input id="amount_max" name="amount_max" type="number" min="0" step="0.01" value="{{ request('amount_max') }}" class="ui-input">
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if($activeFilters)
                        <a href="{{ route('admin.factures.index') }}" class="ui-btn-secondary">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            Reinitialiser
                        </a>
                    @endif
                    <a href="{{ route('admin.factures.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="ui-btn-secondary">
                        <i data-lucide="file-down" class="h-4 w-4" aria-hidden="true"></i>
                        CSV
                    </a>
                    <a href="{{ route('admin.factures.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="ui-btn-secondary">
                        <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                        PDF
                    </a>
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
                <div class="p-4">
                    <x-empty-state icon="file-text" title="Aucune facture" message="Aucune facture ne correspond aux filtres selectionnes." />
                </div>
            @else
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">N facture</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Client</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Navire / escale</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Montant</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Statut</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Date</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($factures as $facture)
                                @php
                                    $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
                                @endphp
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-4">
                                        <a href="{{ route('admin.factures.show', $facture) }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">#{{ $facture->numero_facture }}</a>
                                        @if($facture->verification_status)
                                            <div class="mt-1"><x-badge :status="$facture->verification_status" /></div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :name="$facture->client?->name ?? 'Client'" size="sm" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $facture->client?->name ?? '-' }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $facture->client?->code_client ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $facture->escale?->navire?->nom ?? $facture->escale?->numero_escale ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <p class="font-semibold tabular-nums">{{ number_format($facture->total_ttc, 2, ',', ' ') }} DA</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Reste {{ number_format($facture->reste_a_payer, 2, ',', ' ') }} DA</p>
                                    </td>
                                    <td class="px-4 py-4"><x-badge :status="$status" /></td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ optional($facture->date_facture)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.factures.show', $facture) }}" class="ui-icon-btn" aria-label="Voir facture"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                            @if(! $facture->annuler)
                                                <a href="{{ route('admin.factures.print', $facture) }}" target="_blank" class="ui-icon-btn" aria-label="Imprimer facture"><i data-lucide="printer" class="h-4 w-4"></i></a>
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
                                    <a href="{{ route('admin.factures.show', $facture) }}" class="font-semibold text-primary-600 dark:text-primary-300">#{{ $facture->numero_facture }}</a>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $facture->client?->name ?? '-' }}</p>
                                </div>
                                <x-badge :status="$status" />
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">Montant</span><p class="font-semibold">{{ number_format($facture->total_ttc, 0, ',', ' ') }} DA</p></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Date</span><p class="font-semibold">{{ optional($facture->date_facture)->format('d/m/Y') }}</p></div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('admin.factures.show', $facture) }}" class="ui-btn-secondary flex-1">Voir</a>
                                @if(! $facture->annuler)
                                    <a href="{{ route('admin.factures.print', $facture) }}" target="_blank" class="ui-btn-secondary flex-1">Imprimer</a>
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
