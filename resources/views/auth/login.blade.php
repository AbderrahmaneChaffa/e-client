{{-- // VIEW: login --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-auth-session-status>, <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Connexion';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="{ showPassword: false, submitting: false }">
        <div class="mb-8 text-center">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="mx-auto h-16 w-16 object-contain">
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Bienvenue</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Connectez-vous a votre compte E-Client.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5" @submit="submitting = true">
            @csrf

            <div>
                <label for="email" class="ui-label mb-1">Adresse email</label>
                <input id="email" class="ui-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nom@entreprise.com">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between gap-3">
                    <label for="password" class="ui-label">Mot de passe</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-300">Mot de passe oublie ?</a>
                    @endif
                </div>
                <div class="relative">
                    <input id="password" class="ui-input pr-11" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="••••••••">
                    <button type="button" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" @click="showPassword = ! showPassword" aria-label="Afficher ou masquer le mot de passe">
                        <i data-lucide="eye" x-show="!showPassword" class="h-4 w-4" aria-hidden="true"></i>
                        <i data-lucide="eye-off" x-show="showPassword" x-cloak class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <label for="remember_me" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900" name="remember">
                Se souvenir de moi
            </label>

            <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Connexion...' : 'Se connecter'"></span>
            </button>
        </form>

        <div class="my-6 flex items-center gap-3">
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
            <span class="text-xs font-medium uppercase text-gray-400">ou</span>
            <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        </div>

        <button type="button" class="ui-btn-secondary w-full" disabled>
            <i data-lucide="chrome" class="h-4 w-4" aria-hidden="true"></i>
            Connexion Google indisponible
        </button>

        <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">S'inscrire</a>
        </p>
    </div>
@endsection
