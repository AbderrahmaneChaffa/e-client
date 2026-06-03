@php
    $pageTitle = 'Créer un utilisateur';
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
    <x-page-header
        title="Créer un utilisateur"
        subtitle="Les comptes admin et superadmin disposent d'un accès administratif, les comptes client sont liés à un code client unique."
        :breadcrumbs="[['label' => 'Admin'], ['label' => 'Utilisateurs'], ['label' => 'Créer']]"
    />

    <div class="ui-card p-6">
        @include('admins.users._form', [
            'user' => $user,
            'roles' => $roles,
            'clients' => $clients,
            'action' => route('admin.users.store'),
            'submitLabel' => 'Créer l\'utilisateur',
        ])
    </div>
@endsection
