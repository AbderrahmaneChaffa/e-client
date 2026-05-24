{{-- // VIEW: admin.clients.create --}}
{{-- // ROLE: admin --}}
{{-- // COMPONENTS: <x-page-header>, <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Nouveau client';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6" x-data="{ submitting: false }">
    <x-page-header
        title="Nouveau client"
        subtitle="Creez une fiche client avec ses informations legales et de contact."
        :breadcrumbs="[['label' => 'Clients', 'url' => route('admin.clients.index')], ['label' => 'Creer']]"
    />

    <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-6" @submit="submitting = true">
        @csrf

        <section class="ui-card p-5">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Informations principales</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Identite et reference interne du client.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="code_client" class="ui-label mb-1">Code client</label>
                    <input id="code_client" name="code_client" class="ui-input" value="{{ old('code_client') }}" required>
                    <x-input-error :messages="$errors->get('code_client')" class="mt-2" />
                </div>
                <div>
                    <label for="name" class="ui-label mb-1">Nom / raison sociale</label>
                    <input id="name" name="name" class="ui-input" value="{{ old('name') }}" required>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <label for="email" class="ui-label mb-1">Email</label>
                    <input id="email" name="email" type="email" class="ui-input" value="{{ old('email') }}">
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <label for="telephone" class="ui-label mb-1">Telephone</label>
                    <input id="telephone" name="telephone" class="ui-input" value="{{ old('telephone') }}" placeholder="+213">
                    <x-input-error :messages="$errors->get('telephone')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <label for="adresse" class="ui-label mb-1">Adresse</label>
                    <textarea id="adresse" name="adresse" rows="3" class="ui-input">{{ old('adresse') }}</textarea>
                    <x-input-error :messages="$errors->get('adresse')" class="mt-2" />
                </div>
            </div>
        </section>

        <section class="ui-card p-5">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Identifiants fiscaux</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Registre de commerce, NIF, NIS et article d'imposition.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="rc" class="ui-label mb-1">RC</label>
                    <input id="rc" name="rc" class="ui-input" value="{{ old('rc') }}">
                    <x-input-error :messages="$errors->get('rc')" class="mt-2" />
                </div>
                <div>
                    <label for="nif" class="ui-label mb-1">NIF</label>
                    <input id="nif" name="nif" class="ui-input" value="{{ old('nif') }}">
                    <x-input-error :messages="$errors->get('nif')" class="mt-2" />
                </div>
                <div>
                    <label for="nis" class="ui-label mb-1">NIS</label>
                    <input id="nis" name="nis" class="ui-input" value="{{ old('nis') }}">
                    <x-input-error :messages="$errors->get('nis')" class="mt-2" />
                </div>
                <div>
                    <label for="ai" class="ui-label mb-1">AI</label>
                    <input id="ai" name="ai" class="ui-input" value="{{ old('ai') }}">
                    <x-input-error :messages="$errors->get('ai')" class="mt-2" />
                </div>
            </div>
        </section>

        <div class="sticky bottom-20 z-10 flex flex-col-reverse gap-3 rounded-lg border border-gray-200 bg-white/90 p-3 backdrop-blur dark:border-gray-700 dark:bg-gray-800/90 sm:bottom-4 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.clients.index') }}" class="ui-btn-secondary">Annuler</a>
            <button type="submit" class="ui-btn-primary" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Traitement...' : 'Enregistrer'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
