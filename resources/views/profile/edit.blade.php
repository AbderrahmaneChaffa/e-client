{{-- // VIEW: profile.edit --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-page-header>, <x-avatar>, <x-input-error>, <x-modal> --}}
{{-- // FILTERS: profile tabs --}}
@php
    $pageTitle = 'Mon profil';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6" x-data="{ tab: 'infos', profileSubmitting: false, passwordSubmitting: false }">
    <x-page-header
        title="Mon profil"
        subtitle="Mettez a jour vos informations, votre securite et vos preferences."
        :breadcrumbs="[['label' => 'Compte'], ['label' => 'Profil']]"
    />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <aside class="ui-card p-5 text-center">
            <x-avatar :name="$user->name" size="lg" class="mx-auto" />
            <h2 class="mt-4 text-xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            <div class="mt-4"><x-badge :status="is_object($user->role) ? $user->role->value : $user->role" /></div>
        </aside>

        <main class="space-y-6 lg:col-span-2">
            <section class="ui-card overflow-hidden">
                <div class="border-b border-gray-200 p-2 dark:border-gray-700">
                    <nav class="flex flex-wrap gap-1" aria-label="Onglets profil">
                        @foreach(['infos' => 'Mes informations', 'security' => 'Securite', 'addresses' => 'Adresses', 'preferences' => 'Preferences'] as $key => $label)
                            <button type="button" @click="tab = '{{ $key }}'" class="rounded-lg px-4 py-2 text-sm font-semibold transition" :class="tab === '{{ $key }}' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">{{ $label }}</button>
                        @endforeach
                    </nav>
                </div>

                <div x-show="tab === 'infos'" class="p-5">
                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5" @submit="profileSubmitting = true">
                        @csrf
                        @method('PATCH')

                        <div class="flex items-center gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-900/60">
                            <x-avatar :name="$user->name" size="lg" />
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">Avatar</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">L'upload d'avatar n'est pas encore branche cote serveur.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label for="name" class="ui-label mb-1">Nom complet</label>
                                <input id="name" name="name" type="text" class="ui-input" value="{{ old('name', $user->name) }}" required autocomplete="name">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <label for="email" class="ui-label mb-1">Email</label>
                                <input id="email" name="email" type="email" class="ui-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-800 dark:bg-warning-900/20 dark:text-warning-200">
                                Votre adresse email n'est pas verifiee.
                                <button form="send-verification" class="font-semibold underline">Renvoyer l'email</button>
                            </div>
                        @endif

                        @if (session('status') === 'profile-updated')
                            <p class="rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200">Profil mis a jour.</p>
                        @endif

                        <div class="flex justify-end">
                            <button type="submit" class="ui-btn-primary" :disabled="profileSubmitting">
                                <i data-lucide="loader-circle" x-show="profileSubmitting" x-cloak class="h-4 w-4 animate-spin"></i>
                                <span x-text="profileSubmitting ? 'Traitement...' : 'Enregistrer les modifications'"></span>
                            </button>
                        </div>
                    </form>

                    <form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">@csrf</form>
                </div>

                <div x-show="tab === 'security'" x-cloak class="space-y-6 p-5">
                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" @submit="passwordSubmitting = true">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="current_password" class="ui-label mb-1">Mot de passe actuel</label>
                                <input id="current_password" name="current_password" type="password" class="ui-input" autocomplete="current-password">
                                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                            </div>
                            <div>
                                <label for="password" class="ui-label mb-1">Nouveau mot de passe</label>
                                <input id="password" name="password" type="password" class="ui-input" autocomplete="new-password">
                                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                            </div>
                            <div>
                                <label for="password_confirmation" class="ui-label mb-1">Confirmation</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" class="ui-input" autocomplete="new-password">
                                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                            </div>
                        </div>
                        @if (session('status') === 'password-updated')
                            <p class="rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200">Mot de passe mis a jour.</p>
                        @endif
                        <div class="flex justify-end">
                            <button type="submit" class="ui-btn-primary" :disabled="passwordSubmitting">
                                <i data-lucide="loader-circle" x-show="passwordSubmitting" x-cloak class="h-4 w-4 animate-spin"></i>
                                <span x-text="passwordSubmitting ? 'Traitement...' : 'Changer le mot de passe'"></span>
                            </button>
                        </div>
                    </form>

                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">Supprimer mon compte</p>
                                <p class="text-sm">Cette action est definitive apres confirmation.</p>
                            </div>
                            <button type="button" class="ui-btn-danger" x-on:click="$dispatch('open-modal', 'confirm-user-deletion')">Supprimer</button>
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'addresses'" x-cloak class="p-5">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold">Adresse principale</p>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $user->client?->adresse ?? 'Aucune adresse renseignee' }}</p>
                            </div>
                            <x-badge status="active" label="Par defaut" />
                        </div>
                    </div>
                </div>

                <div x-show="tab === 'preferences'" x-cloak class="p-5">
                    <div class="space-y-4">
                        @foreach(['Notifications email commandes', 'Newsletter', 'Rappels de paiement'] as $preference)
                            <label class="flex items-center justify-between rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <span class="font-medium">{{ $preference }}</span>
                                <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-700 dark:bg-gray-900" checked>
                            </label>
                        @endforeach
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="ui-label mb-1" for="locale">Langue</label>
                                <select id="locale" class="ui-input"><option>Francais</option><option>Arabe</option><option>Anglais</option></select>
                            </div>
                            <div>
                                <label class="ui-label mb-1" for="timezone">Fuseau horaire</label>
                                <select id="timezone" class="ui-input"><option>Africa/Algiers</option><option>UTC</option></select>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <x-modal id="confirm-user-deletion" title="Confirmer la suppression" :show="$errors->userDeletion->isNotEmpty()" max-width="lg">
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-5">
            @csrf
            @method('DELETE')
            <p class="text-sm text-gray-600 dark:text-gray-400">Saisissez votre mot de passe pour confirmer la suppression definitive du compte.</p>
            <div>
                <label for="delete_password" class="ui-label mb-1">Mot de passe</label>
                <input id="delete_password" name="password" type="password" class="ui-input" autocomplete="current-password">
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="ui-btn-secondary" x-on:click="$dispatch('close-modal', 'confirm-user-deletion')">Annuler</button>
                <button type="submit" class="ui-btn-danger">Supprimer mon compte</button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
