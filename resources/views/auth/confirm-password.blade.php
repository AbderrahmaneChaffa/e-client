{{-- // VIEW: password.confirm --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Confirmation';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="{ submitting: false }">
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300">
                <i data-lucide="shield-alert" class="h-7 w-7" aria-hidden="true"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Confirmez votre mot de passe</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Cette zone est protegee. Confirmez votre mot de passe pour continuer.</p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" @submit="submitting = true">
            @csrf
            <div>
                <label for="password" class="ui-label mb-1">Mot de passe</label>
                <input id="password" class="ui-input" type="password" name="password" required autocomplete="current-password">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Verification...' : 'Confirmer'"></span>
            </button>
        </form>
    </div>
@endsection
