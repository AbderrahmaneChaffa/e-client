@php
    $pageTitle = 'Compte en attente de validation';
@endphp

@extends('layouts.guest')
@section('title', $pageTitle)

@section('guest-content')
    <div class="ui-card p-6 text-center sm:p-8">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-warning-50 text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-300">
            <i data-lucide="triangle-alert" class="h-8 w-8" aria-hidden="true"></i>
        </div>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
            Compte non validé
        </h1>

        <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-400">
            {{ $message ?: "Votre compte n'a pas encore été validé par l'administrateur EPO. Veuillez contacter le support." }}
        </p>

        <div class="mt-8">
            <a href="{{ route('login') }}" class="ui-btn-primary w-full">
                <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                Revenir à la connexion
            </a>
        </div>
    </div>
@endsection
