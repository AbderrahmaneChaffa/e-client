{{-- // VIEW: welcome --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: none --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Bienvenue';
@endphp
@extends('layouts.auth')
@section('title', $pageTitle)

@section('content')
    <div class="ui-card p-6 text-center sm:p-8">
        <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="mx-auto h-16 w-16 object-contain">
        <h1 class="mt-5 text-2xl font-bold text-gray-900 dark:text-white">E-Client</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Portail de suivi des factures et paiements.</p>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @auth
                <a href="{{ route('dashboard') }}" class="ui-btn-primary">Ouvrir le tableau de bord</a>
            @else
                <a href="{{ route('login') }}" class="ui-btn-primary">Connexion</a>
                <a href="{{ route('register') }}" class="ui-btn-secondary">Inscription</a>
            @endauth
        </div>
    </div>
@endsection
