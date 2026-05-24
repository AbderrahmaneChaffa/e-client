{{-- // VIEW: verification.notice --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-auth-session-status> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Verification email';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 sm:p-8" x-data="{ submitting: null }">
        <div class="mb-8 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-info-50 text-info-700 dark:bg-info-900/30 dark:text-info-300">
                <i data-lucide="mail-check" class="h-7 w-7" aria-hidden="true"></i>
            </div>
            <h1 class="mt-5 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Verifiez votre email</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Un lien de verification a ete envoye. Vous pouvez demander un nouvel envoi si besoin.</p>
        </div>

        <x-auth-session-status class="mb-4 rounded-lg border border-success-200 bg-success-50 p-3 text-sm text-success-700 dark:border-success-800 dark:bg-success-900/20 dark:text-success-200" :status="session('status')" />

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <form method="POST" action="{{ route('verification.send') }}" @submit="submitting = 'send'">
                @csrf
                <button type="submit" class="ui-btn-primary w-full" :disabled="submitting">
                    <i data-lucide="loader-circle" x-show="submitting === 'send'" x-cloak class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                    Renvoyer
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" @submit="submitting = 'logout'">
                @csrf
                <button type="submit" class="ui-btn-secondary w-full" :disabled="submitting">
                    <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                    Deconnexion
                </button>
            </form>
        </div>
    </div>
@endsection
