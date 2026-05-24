{{-- // VIEW: admin.clients.show --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-avatar>, <x-stat-card>, <x-badge>, <x-empty-state> --}}
{{-- // FILTERS: mini tab filters by status/date visually prepared --}}
@php
    $pageTitle = $client->name;
    $activeFactures = $client->factures->where('annuler', false);
    $totalFacture = $activeFactures->sum('total_ttc');
    $totalDue = $activeFactures->sum('reste_a_payer');
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6" x-data="{ tab: 'factures' }">
    <x-page-header
        :title="$client->name"
        subtitle="Profil client, factures, comptes utilisateurs et informations legales."
        :breadcrumbs="[['label' => 'Clients', 'url' => route('admin.clients.index')], ['label' => $client->name]]"
    >
        <a href="{{ route('admin.clients.edit', $client) }}" class="ui-btn-primary">
            <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
            Editer
        </a>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <aside class="space-y-6">
            <article class="ui-card p-5 text-center">
                <x-avatar :name="$client->name" size="lg" class="mx-auto" />
                <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $client->name }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $client->code_client }}</p>
                <div class="mt-4"><x-badge status="active" /></div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-left text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                        <span class="text-gray-500 dark:text-gray-400">Factures</span>
                        <p class="text-lg font-bold">{{ $client->factures->count() }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900/60">
                        <span class="text-gray-500 dark:text-gray-400">Comptes</span>
                        <p class="text-lg font-bold">{{ $client->users->count() }}</p>
                    </div>
                </div>
            </article>

            <article class="ui-card p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Contact</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Email</dt><dd class="font-medium">{{ $client->email ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Telephone</dt><dd class="font-medium">{{ $client->telephone ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Adresse</dt><dd class="font-medium">{{ $client->adresse ?? '-' }}</dd></div>
                </dl>
            </article>

            <article class="ui-card p-5">
                <h3 class="font-semibold text-gray-900 dark:text-gray-100">Identifiants legaux</h3>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">RC</dt><dd class="font-medium">{{ $client->rc ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">NIF</dt><dd class="font-medium">{{ $client->nif ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">NIS</dt><dd class="font-medium">{{ $client->nis ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">AI</dt><dd class="font-medium">{{ $client->ai ?? '-' }}</dd></div>
                </dl>
            </article>
        </aside>

        <main class="space-y-6 lg:col-span-2">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card title="Total facture" :value="number_format($totalFacture, 0, ',', ' ').' DA'" icon="file-text" color="info" />
                <x-stat-card title="Reste a payer" :value="number_format($totalDue, 0, ',', ' ').' DA'" icon="badge-alert" color="{{ $totalDue > 0 ? 'danger' : 'success' }}" />
                <x-stat-card title="Membre depuis" :value="optional($client->created_at)->format('d/m/Y') ?? '-'" icon="calendar-days" color="primary" />
            </section>

            <section class="ui-card overflow-hidden">
                <div class="border-b border-gray-200 p-2 dark:border-gray-700">
                    <nav class="flex flex-wrap gap-1" aria-label="Onglets client">
                        @foreach(['factures' => 'Factures', 'activite' => 'Activite', 'comptes' => 'Comptes', 'parametres' => 'Parametres'] as $key => $label)
                            <button type="button" @click="tab = '{{ $key }}'" class="rounded-lg px-4 py-2 text-sm font-semibold transition" :class="tab === '{{ $key }}' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">{{ $label }}</button>
                        @endforeach
                    </nav>
                </div>

                <div x-show="tab === 'factures'" class="p-5">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Factures du client</h3>
                        <a href="{{ route('admin.factures.index', ['client_id' => $client->id]) }}" class="ui-btn-secondary">Voir toutes</a>
                    </div>
                    @if($client->factures->isEmpty())
                        <x-empty-state icon="file-text" title="Aucune facture" message="Aucune facture n'est rattachee a ce client." />
                    @else
                        <div class="hidden md:block">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Numero</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Montant</th>
                                        <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase text-gray-500">Statut</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($client->factures->sortByDesc('date_facture')->take(10) as $facture)
                                        @php
                                            $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-3 py-3"><a href="{{ route('admin.factures.show', $facture) }}" class="font-semibold text-primary-600 dark:text-primary-300">#{{ $facture->numero_facture }}</a></td>
                                            <td class="px-3 py-3 text-sm">{{ optional($facture->date_facture)->format('d/m/Y') }}</td>
                                            <td class="px-3 py-3 text-sm font-semibold">{{ number_format($facture->total_ttc, 0, ',', ' ') }} DA</td>
                                            <td class="px-3 py-3"><x-badge :status="$status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="space-y-3 md:hidden">
                            @foreach($client->factures->sortByDesc('date_facture')->take(10) as $facture)
                                @php
                                    $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
                                @endphp
                                <a href="{{ route('admin.factures.show', $facture) }}" class="block rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="font-semibold">#{{ $facture->numero_facture }}</span>
                                        <x-badge :status="$status" />
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ number_format($facture->total_ttc, 0, ',', ' ') }} DA · {{ optional($facture->date_facture)->format('d/m/Y') }}</p>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'activite'" x-cloak class="p-5">
                    <div class="space-y-4">
                        @forelse($client->factures->sortByDesc('updated_at')->take(6) as $facture)
                            <div class="flex gap-3">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-primary-600"></span>
                                <div>
                                    <p class="text-sm font-medium">Facture #{{ $facture->numero_facture }} mise a jour</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ optional($facture->updated_at)->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <x-empty-state icon="activity" title="Aucune activite" message="L'activite du client apparaitra ici." />
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'comptes'" x-cloak class="p-5">
                    @if($client->users->isEmpty())
                        <x-empty-state icon="users" title="Aucun compte utilisateur" message="Aucun utilisateur n'est lie a ce client." />
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach($client->users as $user)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$user->name" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold">{{ $user->name }}</p>
                                            <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div x-show="tab === 'parametres'" x-cloak class="p-5">
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">Zone sensible</p>
                                <p class="text-sm">La suppression conserve l'historique via soft delete.</p>
                            </div>
                            <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" onsubmit="return confirm('Supprimer ce client ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-btn-danger">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
