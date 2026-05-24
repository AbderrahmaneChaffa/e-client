{{-- // VIEW: admin.factures.show --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-badge>, <x-avatar>, <x-empty-state>, <x-modal> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Facture #'.$facture->numero_facture;
    $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
    $steps = [
        ['label' => 'Importee', 'done' => true, 'icon' => 'upload-cloud'],
        ['label' => 'Verifiee', 'done' => (bool) $facture->last_verified_at || $facture->verification_status, 'icon' => 'shield-check'],
        ['label' => 'Mise en ligne', 'done' => (bool) $facture->date_mise_en_ligne, 'icon' => 'send'],
        ['label' => 'Imprimee', 'done' => (bool) $facture->imprimer, 'icon' => 'printer'],
        ['label' => 'Soldee', 'done' => (float) $facture->reste_a_payer <= 0, 'icon' => 'check-circle-2'],
    ];
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6">
    <x-page-header
        :title="'Facture #'.$facture->numero_facture"
        :subtitle="'Emise le '.(optional($facture->date_facture)->format('d/m/Y') ?? '-')"
        :breadcrumbs="[['label' => 'Factures', 'url' => route('admin.factures.index')], ['label' => '#'.$facture->numero_facture]]"
    >
        <x-badge :status="$status" />
        @if($facture->verification_status)
            <x-badge :status="$facture->verification_status" />
        @endif
        @if(! $facture->annuler)
            <a href="{{ route('admin.factures.print', $facture) }}" target="_blank" class="ui-btn-primary">
                <i data-lucide="printer" class="h-4 w-4" aria-hidden="true"></i>
                Imprimer
            </a>
        @endif
    </x-page-header>

    <section class="ui-card p-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            @foreach($steps as $step)
                <div class="relative rounded-lg border p-4 {{ $step['done'] ? 'border-success-200 bg-success-50 dark:border-success-900/60 dark:bg-success-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50' }}">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full {{ $step['done'] ? 'bg-success-600 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                            <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $step['done'] ? 'Termine' : 'En attente' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <main class="space-y-6 xl:col-span-2">
            <article class="ui-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Prestations facturees</h2>
                </div>
                @if($facture->prestations->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="list" title="Aucune prestation" message="Aucune ligne de prestation n'est rattachee a cette facture." />
                    </div>
                @else
                    <div class="hidden md:block">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/60">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Article</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Libelle</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qte</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Prix</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total TTC</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($facture->prestations as $prestation)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-4 py-3 text-sm font-medium">{{ $prestation->article }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $prestation->libelle }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ number_format($prestation->quantite, 0, ',', ' ') }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ number_format($prestation->prix_unitaire, 2, ',', ' ') }}</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold">{{ number_format($prestation->total_ttc, 2, ',', ' ') }} DA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="space-y-3 p-4 md:hidden">
                        @foreach($facture->prestations as $prestation)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <p class="font-semibold">{{ $prestation->libelle }}</p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $prestation->article }} · Qte {{ $prestation->quantite }}</p>
                                <p class="mt-2 font-semibold">{{ number_format($prestation->total_ttc, 2, ',', ' ') }} DA</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="ui-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paiements</h2>
                </div>
                @if($facture->paiements->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="credit-card" title="Aucun paiement" message="Aucun reglement n'est encore associe a cette facture." />
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($facture->paiements->sortByDesc('date_paiement') as $payment)
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">Recu {{ $payment->recu ?? '-' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $payment->date_paiement ? \Illuminate\Support\Carbon::parse($payment->date_paiement)->format('d/m/Y') : '-' }} · {{ $payment->banque ?? 'Banque non renseignee' }}</p>
                                </div>
                                <p class="text-lg font-bold text-success-700 dark:text-success-300">{{ number_format($payment->montant, 2, ',', ' ') }} DA</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </main>

        <aside class="space-y-6">
            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Total</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Total HT</dt><dd class="font-semibold">{{ number_format($facture->total_ht, 2, ',', ' ') }} DA</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">TVA</dt><dd class="font-semibold">{{ number_format($facture->total_tva, 2, ',', ' ') }} DA</dd></div>
                    <div class="flex justify-between gap-4 border-t border-gray-200 pt-3 dark:border-gray-700"><dt class="text-gray-900 dark:text-gray-100">Total TTC</dt><dd class="text-lg font-bold">{{ number_format($facture->total_ttc, 2, ',', ' ') }} DA</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500 dark:text-gray-400">Reste a payer</dt><dd class="font-bold text-danger-700 dark:text-danger-300">{{ number_format($facture->reste_a_payer, 2, ',', ' ') }} DA</dd></div>
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Client</h2>
                <div class="mt-4 flex items-center gap-3">
                    <x-avatar :name="$facture->client?->name ?? 'Client'" size="md" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $facture->client?->name ?? '-' }}</p>
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $facture->client?->code_client ?? '-' }}</p>
                    </div>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Email</dt><dd class="font-medium">{{ $facture->client?->email ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Adresse</dt><dd class="font-medium">{{ $facture->client?->adresse ?? '-' }}</dd></div>
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Escale</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500 dark:text-gray-400">Navire</dt><dd class="font-medium">{{ $facture->escale?->navire?->nom ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Numero escale</dt><dd class="font-medium">{{ $facture->escale?->numero_escale ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500 dark:text-gray-400">Consignataire</dt><dd class="font-medium">{{ $facture->escale?->consignataire ?? '-' }}</dd></div>
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Notes internes</h2>
                <textarea rows="4" class="ui-input mt-3" placeholder="Note admin non visible client">{{ $facture->description }}</textarea>
                <button type="button" class="ui-btn-secondary mt-3 w-full" disabled>Enregistrer la note</button>
            </article>
        </aside>
    </div>
</div>
@endsection
