{{-- // VIEW: dashboard --}}
{{-- // ROLE: both --}}
{{-- // COMPONENTS: <x-page-header> --}}
{{-- // FILTERS: none --}}
@php
    $pageTitle = 'Dashboard';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
    <x-page-header title="Dashboard" subtitle="Redirection automatique selon votre role." />
    <div class="ui-card p-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">Vous etes connecte.</p>
    </div>
@endsection
