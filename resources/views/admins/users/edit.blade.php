@php
    $pageTitle = 'Modifier l\'utilisateur';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
    <x-page-header
        title="Modifier l'utilisateur"
        subtitle="Ajustez les informations du compte et la validation."
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Utilisateurs'], ['label' => $user->name]]"
    />

    <div class="ui-card p-6">
        @include('admins.users._form', [
            'user' => $user,
            'roles' => $roles,
            'clients' => $clients,
            'action' => route('admin.users.update', $user),
            'submitLabel' => 'Mettre à jour',
        ])
    </div>
@endsection
