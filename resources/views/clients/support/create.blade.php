@php
    $pageTitle = 'Nouveau ticket';
@endphp

@extends('clients.layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Créer un ticket de support"
        subtitle="Votre demande sera rattachée au dossier client et, si besoin, à une facture précise."
        :breadcrumbs="[['label' => 'Client'], ['label' => 'Support', 'url' => route('client.support.index')], ['label' => 'Nouveau ticket']]"
    >
        <a href="{{ route('client.support.index') }}" class="ui-btn-secondary min-h-11">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Retour
        </a>
    </x-page-header>

    @if($facture)
        <section class="ui-card p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Facture concernée</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Numéro</p>
                    <p class="mt-1 font-semibold">#{{ $facture->numero_facture }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</p>
                    <p class="mt-1 font-semibold">{{ $facture->date_facture?->format('d/m/Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Reste à payer</p>
                    <p class="mt-1 font-semibold text-danger-700 dark:text-danger-300">{{ number_format((float) $facture->reste_a_payer, 0, ',', ' ') }} DA</p>
                </div>
            </div>
        </section>
    @endif

    <section class="ui-card p-5">
        <form method="POST" action="{{ route('client.support.store') }}" class="space-y-5">
            @csrf

            @if(! $facture)
                <div>
                    <x-input-label for="facture_id" value="Facture concernée" />
                    <select id="facture_id" name="facture_id" class="ui-input mt-1">
                        <option value="">Aucune facture précise</option>
                        @foreach($factures as $invoice)
                            <option value="{{ $invoice->id }}" @selected((string) old('facture_id') === (string) $invoice->id)>
                                #{{ $invoice->numero_facture }} · {{ optional($invoice->date_facture)->format('d/m/Y') }} · {{ number_format((float) $invoice->reste_a_payer, 0, ',', ' ') }} DA
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('facture_id')" class="mt-2" />
                </div>
            @else
                <input type="hidden" name="facture_id" value="{{ $facture->id }}">
            @endif

            <div>
                <x-input-label for="sujet" value="Sujet" />
                <x-text-input id="sujet" name="sujet" type="text" class="mt-1" value="{{ old('sujet', $facture ? 'Question sur la facture #'.$facture->numero_facture : '') }}" required />
                <x-input-error :messages="$errors->get('sujet')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="priorite" value="Priorité" />
                <select id="priorite" name="priorite" class="ui-input mt-1">
                    <option value="normal" @selected(old('priorite', 'normal') === 'normal')>Normale</option>
                    <option value="urgent" @selected(old('priorite') === 'urgent')>Urgente</option>
                </select>
                <x-input-error :messages="$errors->get('priorite')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="message" value="Message" />
                <textarea
                    id="message"
                    name="message"
                    rows="8"
                    class="ui-input mt-1"
                    required
                    placeholder="Décrivez précisément votre demande, les références utiles et l'impact constaté."
                >{{ old('message') }}</textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />
            </div>

            <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-end dark:border-gray-700">
                <a href="{{ route('client.support.index') }}" class="ui-btn-secondary min-h-11">Annuler</a>
                <button type="submit" class="ui-btn-primary min-h-11">
                    <i data-lucide="send" class="h-4 w-4" aria-hidden="true"></i>
                    Envoyer le ticket
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
