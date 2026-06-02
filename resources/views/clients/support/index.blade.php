@php
    $pageTitle = 'Support client';
    $filtering = (bool) request('statut');
@endphp

@extends('clients.layouts.app')
@section('title', $pageTitle)

@section('content')
<div x-data="{ loading: false }" class="space-y-6">
    <x-page-header
        title="Support client"
        subtitle="Suivi de vos tickets liés aux factures."
        :breadcrumbs="[['label' => 'Client'], ['label' => 'Support']]"
    >
        <a href="{{ route('client.support.create') }}" class="ui-btn-primary min-h-11">
            <i data-lucide="message-circle" class="h-4 w-4" aria-hidden="true"></i>
            Nouveau ticket
        </a>
    </x-page-header>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card title="Tickets" :value="number_format((int) ($stats->total_count ?? 0), 0, ',', ' ')" icon="message-circle" color="primary" />
        <x-stat-card title="Ouverts" :value="number_format((int) ($stats->ouverts ?? 0), 0, ',', ' ')" icon="clock-3" color="warning" />
        <x-stat-card title="En cours" :value="number_format((int) ($stats->en_cours ?? 0), 0, ',', ' ')" icon="loader-circle" color="info" />
        <x-stat-card title="Résolus" :value="number_format((int) ($stats->resolus ?? 0), 0, ',', ' ')" icon="check-circle-2" color="success" />
    </section>

    <form method="GET" action="{{ route('client.support.index') }}" class="ui-card p-4" @submit="loading = true">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <label for="statut" class="ui-label mb-1">Statut</label>
                <select id="statut" name="statut" class="ui-input min-h-11" @change="loading = true; $el.form.submit()">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-3">
                <label for="per_page" class="ui-label mb-1">Lignes</label>
                <select id="per_page" name="per_page" class="ui-input min-h-11" @change="loading = true; $el.form.submit()">
                    @foreach([10, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) $perPage === $size)>{{ $size }} / page</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-5">
                @if($filtering)
                    <a href="{{ route('client.support.index') }}" class="ui-btn-secondary min-h-11">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                        Réinitialiser
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="ui-card relative overflow-hidden">
        <div x-show="loading" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-gray-950/75">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow dark:bg-gray-800 dark:text-gray-200">
                <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                Chargement...
            </span>
        </div>

        @if($tickets->isEmpty())
            <x-empty-state
                icon="message-circle"
                title="Aucun ticket"
                message="Aucun ticket ne correspond aux critères sélectionnés."
                action-label="Créer un ticket"
                :action-route="route('client.support.create')"
            />
        @else
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/60">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Facture</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Statut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Priorité</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($tickets as $ticket)
                            <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-4">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->sujet }}</p>
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($ticket->message, 120) }}</p>
                                        @if($ticket->reponse_admin)
                                            <p class="mt-2 rounded-lg border border-success-200 bg-success-50 px-3 py-2 text-sm text-success-900 dark:border-success-900/50 dark:bg-success-900/20 dark:text-success-100">
                                                <span class="font-semibold">Réponse support :</span>
                                                {{ \Illuminate\Support\Str::limit($ticket->reponse_admin, 120) }}
                                            </p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                    @if($ticket->facture)
                                        <a href="{{ route('client.factures.show', $ticket->facture) }}" class="font-semibold text-primary-700 hover:underline dark:text-primary-300">
                                            #{{ $ticket->facture->numero_facture }}
                                        </a>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Non liée</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <x-badge :status="$ticket->statut" />
                                </td>
                                <td class="px-4 py-4">
                                    <x-badge :status="$ticket->priorite === 'urgent' ? 'warning' : 'active'" :label="$ticket->priorite === 'urgent' ? 'Urgente' : 'Normale'" />
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $ticket->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-4 text-right">
                                    @if($ticket->facture)
                                        <a href="{{ route('client.factures.show', $ticket->facture) }}" class="ui-btn-secondary min-h-11">
                                            <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                                            Ouvrir
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="space-y-3 p-4 md:hidden">
                @foreach($tickets as $ticket)
                    <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $ticket->sujet }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $ticket->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <x-badge :status="$ticket->statut" />
                        </div>

                        <p class="mt-3 line-clamp-3 text-sm text-gray-600 dark:text-gray-400">{{ $ticket->message }}</p>

                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Facture</dt>
                                <dd class="font-semibold text-gray-900 dark:text-gray-100">
                                    @if($ticket->facture)
                                        #{{ $ticket->facture->numero_facture }}
                                    @else
                                        Non liée
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Priorité</dt>
                                <dd><x-badge :status="$ticket->priorite === 'urgent' ? 'warning' : 'active'" :label="$ticket->priorite === 'urgent' ? 'Urgente' : 'Normale'" /></dd>
                            </div>
                        </dl>

                        @if($ticket->reponse_admin)
                            <div class="mt-4 rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-900 dark:border-success-900/50 dark:bg-success-900/20 dark:text-success-100">
                                <p class="font-semibold">Réponse support</p>
                                <p class="mt-1">{{ $ticket->reponse_admin }}</p>
                            </div>
                        @endif

                        <div class="mt-4">
                            @if($ticket->facture)
                                <a href="{{ route('client.factures.show', $ticket->facture) }}" class="ui-btn-secondary min-h-11 w-full">
                                    <i data-lucide="eye" class="h-4 w-4" aria-hidden="true"></i>
                                    Ouvrir la facture
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                    Affichage {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }} sur {{ $tickets->total() }} ticket(s)
                </div>
                {{ $tickets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
