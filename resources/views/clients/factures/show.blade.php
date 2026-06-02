@php
    $pageTitle = 'Facture #' . $facture->numero_facture;
    $status = $facture->annuler ? 'annulee' : ((float) $facture->reste_a_payer <= 0 ? 'paye' : 'impaye');
    $steps = [
        ['label' => 'Émise', 'done' => true, 'icon' => 'file-text'],
        ['label' => 'Mise en ligne', 'done' => (bool) $facture->date_mise_en_ligne, 'icon' => 'send'],
        ['label' => 'Paiement partiel', 'done' => $facture->paiements->isNotEmpty(), 'icon' => 'credit-card'],
        ['label' => 'Soldée', 'done' => (float) $facture->reste_a_payer <= 0, 'icon' => 'check-circle-2'],
    ];
@endphp

@extends('clients.layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6">
    <x-page-header
        :title="'Facture #' . $facture->numero_facture"
        :subtitle="'Date facture : ' . (optional($facture->date_facture)->format('d/m/Y') ?? '-')"
        :breadcrumbs="[['label' => 'Mes factures', 'url' => route('client.factures.index')], ['label' => '#' . $facture->numero_facture]]"
    >
        <x-badge :status="$status" />
        @if(! $facture->annuler)
            <a href="{{ route('client.invoices.facture.print', $facture) }}" target="_blank" class="ui-btn-secondary min-h-11">
                <i data-lucide="download" class="h-4 w-4" aria-hidden="true"></i>
                Facture PDF
            </a>
        @endif
        <a href="{{ route('client.support.create', ['facture_id' => $facture->id]) }}" class="ui-btn-primary min-h-11">
            <i data-lucide="message-circle" class="h-4 w-4" aria-hidden="true"></i>
            Contacter le support
        </a>
    </x-page-header>

    <section class="ui-card p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            @foreach($steps as $step)
                <div class="rounded-lg border p-4 {{ $step['done'] ? 'border-success-200 bg-success-50 dark:border-success-900/60 dark:bg-success-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50' }}">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full {{ $step['done'] ? 'bg-success-600 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                            <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold">{{ $step['label'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $step['done'] ? 'Terminé' : 'En attente' }}</p>
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
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Articles / prestations</h2>
                </div>
                @if($facture->prestations->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="list" title="Aucune ligne" message="Aucune prestation détaillée n'est disponible." />
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($facture->prestations as $prestation)
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $prestation->libelle }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $prestation->article }} · Qté {{ number_format($prestation->quantite, 0, ',', ' ') }}
                                    </p>
                                </div>
                                <p class="font-bold tabular-nums">{{ number_format($prestation->total_ttc, 2, ',', ' ') }} DA</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="ui-card overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-gray-100">Paiements associés</h2>
                </div>
                @if($facture->paiements->isEmpty())
                    <div class="p-4">
                        <x-empty-state icon="credit-card" title="Aucun paiement" message="Aucun règlement n'est encore associé à cette facture." />
                    </div>
                @else
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($facture->paiements->sortByDesc('date_paiement') as $payment)
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold">Reçu {{ $payment->recu ?? '-' }}</p>
                                    <p class="font-semibold">Chèque {{ $payment->numero_cheque ?? '-' }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $payment->date_paiement ? \Illuminate\Support\Carbon::parse($payment->date_paiement)->format('d/m/Y') : '-' }}
                                        · {{ $payment->banque ?? '-' }}
                                    </p>
                                </div>
                                <p class="font-bold text-success-700 dark:text-success-300">
                                    {{ number_format($payment->montant, 2, ',', ' ') }} DA
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </main>

        <aside class="space-y-6">
            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Récapitulatif</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Total HT</dt>
                        <dd class="font-semibold">{{ number_format($facture->total_ht, 2, ',', ' ') }} DA</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">TVA</dt>
                        <dd class="font-semibold">{{ number_format($facture->total_tva, 2, ',', ' ') }} DA</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-gray-200 pt-3 dark:border-gray-700">
                        <dt>Total TTC</dt>
                        <dd class="text-lg font-bold">{{ number_format($facture->total_ttc, 2, ',', ' ') }} DA</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Reste à payer</dt>
                        <dd class="font-bold text-danger-700 dark:text-danger-300">{{ number_format($facture->reste_a_payer, 2, ',', ' ') }} DA</dd>
                    </div>
                </dl>
            </article>

            <article class="ui-card p-5">
                <h2 class="font-semibold text-gray-900 dark:text-gray-100">Adresse / escale</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Navire</dt>
                        <dd class="font-medium">{{ $facture->escale?->navire?->nom ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Escale</dt>
                        <dd class="font-medium">{{ $facture->escale?->numero_escale ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Poste quai</dt>
                        <dd class="font-medium">{{ $facture->escale?->poste_quai ?? '-' }}</dd>
                    </div>
                </dl>
            </article>
        </aside>
    </div>
</div>
@endsection
