{{-- // VIEW: password.request --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-auth-session-status>, <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Mot de passe oublie';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="{ submitting: false }">
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                <i data-lucide="key-round" class="h-7 w-7" aria-hidden="true"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Reinitialiser le mot de passe</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Recevez un lien de reinitialisation sur votre adresse email.</p>
        </div>

        <x-auth-session-status class="mb-4 rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" @submit="submitting = true">
            @csrf
            <div>
                <label for="email" class="ui-label mb-1">Adresse email</label>
                <input id="email" class="ui-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Envoi...' : 'Envoyer le lien'"></span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">Retour a la connexion</a>
        </p>
    </div>
@endsection
