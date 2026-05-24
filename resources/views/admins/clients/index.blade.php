{{-- // VIEW: admin.clients.index --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-search-input>, <x-date-range-picker>, <x-badge>, <x-avatar>, <x-empty-state>, <x-loading-skeleton> --}}
{{-- // FILTERS: search, status visual, date range, period, sort, per_page, advanced legal identifiers --}}
@php
    $pageTitle = 'Clients';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
@php
    $activeFilters = collect(['search', 'date_from', 'date_to', 'period', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

<div x-data="{ state: 'data', filtering: false, selected: [], advanced: false }" class="space-y-6">
    <x-page-header
        title="Clients"
        subtitle="Gerez les comptes clients, leurs identifiants fiscaux et leurs factures."
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Clients']]"
    >
        <a href="{{ route('admin.clients.create') }}" class="ui-btn-primary">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            Ajouter un client
        </a>
    </x-page-header>

    <div x-show="state === 'loading'" x-cloak>
        <x-loading-skeleton rows="5" />
    </div>

    <div x-show="state === 'error'" x-cloak class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
        <div class="flex items-center justify-between gap-4">
            <p class="text-sm font-medium">Impossible de charger les clients.</p>
            <button class="ui-btn-secondary" @click="state = 'data'">Reessayer</button>
        </div>
    </div>

    <div class="space-y-4">
        <form method="GET" action="{{ route('admin.clients.index') }}" class="ui-card p-4" @submit="filtering = true">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-search-input name="search" placeholder="Recherche nom, email, NIF, RC..." />
                </div>
                <div class="lg:col-span-3">
                    <select name="period" class="ui-input" aria-label="Periode">
                        <option value="">Toute periode</option>
                        <option value="today" @selected(request('period') === 'today')>Aujourd'hui</option>
                        <option value="week" @selected(request('period') === 'week')>Cette semaine</option>
                        <option value="month" @selected(request('period') === 'month')>Ce mois</option>
                        <option value="custom" @selected(request('period') === 'custom')>Personnalise</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="per_page" class="ui-input" aria-label="Nombre d'entrees par page">
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
                    @if($activeFilters)
                        <a href="{{ route('admin.clients.index') }}" class="ui-btn-secondary">
                            <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                            Reset
                        </a>
                    @endif
                    <button type="button" class="ui-btn-secondary relative" @click="advanced = ! advanced">
                        <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
                        Avance
                        @if($activeFilters)
                            <span class="absolute -right-1 -top-1 rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] text-white">{{ $activeFilters }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div x-show="advanced" x-cloak class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <x-date-range-picker />
                    <div>
                        <label class="ui-label mb-1" for="sort">Tri</label>
                        <select id="sort" name="sort" class="ui-input">
                            <option value="name" @selected(request('sort') === 'name')>Nom</option>
                            <option value="code_client" @selected(request('sort') === 'code_client')>Code client</option>
                            <option value="created_at" @selected(request('sort') === 'created_at')>Date creation</option>
                            <option value="nif" @selected(request('sort') === 'nif')>NIF</option>
                        </select>
                    </div>
                    <div>
                        <label class="ui-label mb-1" for="dir">Direction</label>
                        <select id="dir" name="dir" class="ui-input">
                            <option value="asc" @selected(request('dir') !== 'desc')>Ascendant</option>
                            <option value="desc" @selected(request('dir') === 'desc')>Descendant</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('admin.clients.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="ui-btn-secondary">
                        <i data-lucide="file-down" class="h-4 w-4" aria-hidden="true"></i>
                        CSV
                    </a>
                    <a href="{{ route('admin.clients.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="ui-btn-secondary">
                        <i data-lucide="file-text" class="h-4 w-4" aria-hidden="true"></i>
                        PDF
                    </a>
                </div>
            </div>
        </form>

        <div x-show="selected.length" x-cloak class="ui-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"><span x-text="selected.length"></span> client(s) selectionne(s)</p>
            <div class="flex flex-wrap gap-2">
                <button class="ui-btn-secondary" type="button">Exporter</button>
                <button class="ui-btn-danger" type="button">Supprimer</button>
            </div>
        </div>

        <div class="ui-card relative overflow-hidden">
            <div x-show="filtering" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-gray-900/70">
                <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold shadow dark:bg-gray-800">
                    <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                    Filtrage...
                </span>
            </div>

            @if($clients->isEmpty())
                <div class="p-4">
                    <x-empty-state icon="users" title="Aucun client" message="Aucun client ne correspond aux filtres actuels." :action-route="route('admin.clients.create')" action-label="Ajouter un client" />
                </div>
            @else
                <div class="hidden md:block">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/60">
                            <tr>
                                <th scope="col" class="w-10 px-4 py-3">
                                    <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900" @change="selected = $event.target.checked ? @js($clients->pluck('id')->values()) : []">
                                </th>
                                @foreach([
                                    'name' => 'Client',
                                    'code_client' => 'Code',
                                    'nif' => 'NIF',
                                    'created_at' => 'Inscription',
                                ] as $sort => $label)
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $sort, 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 hover:text-primary-600 dark:hover:text-primary-300">
                                            {{ $label }}
                                            <i data-lucide="{{ request('sort') === $sort ? (request('dir') === 'desc' ? 'arrow-down' : 'arrow-up') : 'arrow-up-down' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        </a>
                                    </th>
                                @endforeach
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Statut</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($clients as $client)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-4">
                                        <input type="checkbox" value="{{ $client->id }}" x-model="selected" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900">
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <x-avatar :name="$client->name" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.clients.show', $client) }}" class="truncate text-sm font-semibold text-gray-900 hover:text-primary-600 dark:text-gray-100 dark:hover:text-primary-300">{{ $client->name }}</a>
                                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{$client->users->first()?->email ?? 'Email non renseigne' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $client->code_client }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $client->nif ?? '-' }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ optional($client->created_at)->format('d/m/Y') }}</td>
                                    <td class="px-4 py-4"><x-badge status="active" /></td>
                                    <td class="px-4 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.clients.show', $client) }}" class="ui-icon-btn" aria-label="Voir {{ $client->name }}"><i data-lucide="eye" class="h-4 w-4"></i></a>
                                            <a href="{{ route('admin.clients.edit', $client) }}" class="ui-icon-btn" aria-label="Modifier {{ $client->name }}"><i data-lucide="pencil" class="h-4 w-4"></i></a>
                                            <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" x-data="{ deleting: false }" @submit="deleting = true; return confirm('Supprimer ce client ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-icon-btn text-danger-600 dark:text-danger-300" :disabled="deleting" aria-label="Supprimer {{ $client->name }}">
                                                    <i data-lucide="loader-circle" x-show="deleting" x-cloak class="h-4 w-4 animate-spin"></i>
                                                    <i data-lucide="trash-2" x-show="!deleting" class="h-4 w-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @foreach($clients as $client)
                        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-avatar :name="$client->name" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $client->name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $client->code_client }}</p>
                                    </div>
                                </div>
                                <x-badge status="active" />
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                <div><span class="text-gray-500 dark:text-gray-400">NIF</span><p class="font-medium">{{ $client->nif ?? '-' }}</p></div>
                                <div><span class="text-gray-500 dark:text-gray-400">Factures</span><p class="font-medium">{{ $client->factures_count ?? $client->factures()->count() }}</p></div>
                            </div>
                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('admin.clients.show', $client) }}" class="ui-btn-secondary flex-1">Voir</a>
                                <a href="{{ route('admin.clients.edit', $client) }}" class="ui-btn-secondary flex-1">Editer</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    {{-- <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        Affichage {{ $clients->firstItem() }}-{{ $clients->lastItem() }} sur {{ $clients->total() }} clients
                    </div> --}}
                    {{ $clients->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
