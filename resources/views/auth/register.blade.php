{{-- // VIEW: register --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-input-error> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Inscription';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="registerForm()">
        <div class="mb-8 text-center">
            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="mx-auto h-16 w-16 object-contain">
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Creer un compte</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Accedez a vos factures et paiements en ligne.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5" @submit="submitting = true; fullName = `${firstName} ${lastName}`.trim()">
            @csrf
            <input type="hidden" name="name" x-model="fullName">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="ui-label mb-1">Prenom</label>
                    <input id="first_name" class="ui-input" type="text" x-model="firstName" required autofocus autocomplete="given-name">
                </div>
                <div>
                    <label for="last_name" class="ui-label mb-1">Nom</label>
                    <input id="last_name" class="ui-input" type="text" x-model="lastName" required autocomplete="family-name">
                </div>
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />

            <div>
                <label for="email" class="ui-label mb-1">Adresse email</label>
                <input id="email" class="ui-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="ui-label mb-1">Mot de passe</label>
                <div class="relative">
                    <input id="password" class="ui-input pr-11" :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" @click="showPassword = ! showPassword" aria-label="Afficher ou masquer le mot de passe">
                        <i data-lucide="eye" x-show="!showPassword" class="h-4 w-4" aria-hidden="true"></i>
                        <i data-lucide="eye-off" x-show="showPassword" x-cloak class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="mt-2 h-2 rounded-full bg-gray-100 dark:bg-gray-700" aria-hidden="true">
                    <div class="h-2 rounded-full transition-all" :class="strengthClass()" :style="`width: ${strength()}%`"></div>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="strengthLabel()"></p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="ui-label mb-1">Confirmer le mot de passe</label>
                <input id="password_confirmation" class="ui-input" type="password" name="password_confirmation" required autocomplete="new-password">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <label class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                <input type="checkbox" required class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900">
                <span>J'accepte les conditions generales d'utilisation.</span>
            </label>

            <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                <i data-lucide="loader-circle" x-show="submitting" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                <span x-text="submitting ? 'Creation...' : 'Creer mon compte'"></span>
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600 dark:text-gray-400">
            Deja inscrit ?
            <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">Se connecter</a>
        </p>
    </div>

    <script>
        function registerForm() {
            return {
                firstName: @js(old('first_name', '')),
                lastName: @js(old('last_name', '')),
                fullName: @js(old('name', '')),
                password: '',
                showPassword: false,
                submitting: false,
                strength() {
                    let score = 0;
                    if (this.password.length >= 8) score += 35;
                    if (/[A-Z]/.test(this.password)) score += 20;
                    if (/[0-9]/.test(this.password)) score += 20;
                    if (/[^A-Za-z0-9]/.test(this.password)) score += 25;
                    return Math.min(score, 100);
                },
                strengthClass() {
                    const score = this.strength();
                    if (score < 45) return 'bg-danger-500';
                    if (score < 75) return 'bg-warning-500';
                    return 'bg-success-500';
                },
                strengthLabel() {
                    const score = this.strength();
                    if (!this.password) return 'Utilisez au moins 8 caracteres.';
                    if (score < 45) return 'Mot de passe faible';
                    if (score < 75) return 'Mot de passe correct';
                    return 'Mot de passe robuste';
                }
            }
        }
    </script>
@endsection
