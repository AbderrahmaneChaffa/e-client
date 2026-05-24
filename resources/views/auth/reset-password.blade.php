{{-- // VIEW: password.reset --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Nouveau mot de passe';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="{ showPassword: false, submitting: false }">
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                <i data-lucide="lock-keyhole" class="h-7 w-7" aria-hidden="true"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Choisir un nouveau mot de passe</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Votre nouveau mot de passe sera actif immediatement.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-5" @submit="submitting = true">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="ui-label mb-1">Adresse email</label>
                <input id="email" class="ui-input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="ui-label mb-1">Nouveau mot de passe</label>
                <div class="relative">
                    <input id="password" class="ui-input pr-11" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" @click="showPassword = ! showPassword" aria-label="Afficher ou masquer le mot de passe">
                        <i data-lucide="eye" x-show="!showPassword" class="h-4 w-4" aria-hidden="true"></i>
                        <i data-lucide="eye-off" x-show="showPassword" x-cloak class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="ui-label mb-1">Confirmation</label>
                <input id="password_confirmation" class="ui-input" type="password" name="password_confirmation" required autocomplete="new-password">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Enregistrement...' : 'Reinitialiser'"></span>
            </button>
        </form>
    </div>
@endsection
