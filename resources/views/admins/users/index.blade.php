@php
    $pageTitle = 'Gestion des utilisateurs';
    $activeFilters = collect(['search', 'role', 'validation', 'per_page'])
        ->filter(fn ($key) => request()->filled($key))
        ->count();
    $currentUser = auth()->user();
@endphp
@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
    <div class="space-y-6">
        <x-page-header
            title="Gestion des utilisateurs"
            subtitle="Créez, validez et sécurisez les comptes admin, superadmin et client."
            :breadcrumbs="[['label' => 'Admin'], ['label' => 'Utilisateurs']]"
        >
            @if($currentUser?->isSuperAdmin())
                <a href="{{ route('admin.users.create') }}" class="ui-btn-primary">
                    <i data-lucide="user-plus" class="h-4 w-4" aria-hidden="true"></i>
                    Nouvel utilisateur
                </a>
            @endif
        </x-page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card title="Utilisateurs" :value="number_format((int) ($summary['total'] ?? 0), 0, ',', ' ')" icon="users" color="primary" />
            <x-stat-card title="Validés" :value="number_format((int) ($summary['validated'] ?? 0), 0, ',', ' ')" icon="check-circle-2" color="success" />
            <x-stat-card title="En attente" :value="number_format((int) ($summary['pending'] ?? 0), 0, ',', ' ')" icon="clock-3" color="warning" />
            <x-stat-card title="Superadmins" :value="number_format((int) ($summary['superadmins'] ?? 0), 0, ',', ' ')" icon="shield" color="info" />
        </div>

        <x-data-table
            :rows="$users"
            :columns="[
                ['key' => 'name', 'label' => 'Utilisateur'],
                ['key' => 'role', 'label' => 'Rôle'],
                ['key' => 'client', 'label' => 'Client'],
                ['key' => 'is_validated', 'label' => 'Validation'],
                ['key' => 'created_at', 'label' => 'Créé le'],
                ['key' => '', 'label' => 'Actions'],
            ]"
            empty-title="Aucun utilisateur"
            empty-message="Aucun utilisateur ne correspond aux filtres sélectionnés."
            class="ui-card"
        >
            <x-slot name="filters">
                <form method="GET" action="{{ route('admin.users.index') }}" class="space-y-4" @submit="busy = true">
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
                        <div class="lg:col-span-4">
                            <x-search-input name="search" placeholder="Nom, email, code client..." :value="request('search')" />
                        </div>
                        <div class="lg:col-span-2">
                            <select name="role" class="ui-input" aria-label="Filtrer par rôle">
                                <option value="">Tous les rôles</option>
                                @foreach(['client' => 'Client', 'admin' => 'Admin', 'superadmin' => 'Superadmin'] as $value => $label)
                                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <select name="validation" class="ui-input" aria-label="Filtrer par validation">
                                <option value="">Tous les statuts</option>
                                <option value="validated" @selected(request('validation') === 'validated')>Validés</option>
                                <option value="pending" @selected(request('validation') === 'pending')>En attente</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <select name="per_page" class="ui-input" aria-label="Nombre d'éléments par page">
                                @foreach([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }} / page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-2 lg:col-span-2 lg:justify-end">
                            <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
                            <input type="hidden" name="direction" value="{{ request('direction', request('dir', 'desc')) }}">
                            <button type="submit" class="ui-btn-primary flex-1 lg:flex-none">
                                <i data-lucide="filter" class="h-4 w-4" aria-hidden="true"></i>
                                Filtrer
                            </button>
                            @if($activeFilters)
                                <a href="{{ route('admin.users.index') }}" class="ui-btn-secondary flex-1 lg:flex-none">
                                    <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                                    Réinitialiser
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </x-slot>

            @foreach($users as $user)
                @php
                    $roleKey = $user->isSuperAdmin() ? 'superadmin' : ($user->isAdmin() ? 'admin' : 'client');
                    $canEdit = $currentUser?->isSuperAdmin();
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$user->name" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4"><x-badge :status="$roleKey" /></td>
                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                        @if($user->client)
                            <div class="min-w-0">
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->client->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->client->code_client }}</p>
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-4">
                        <x-badge :status="$user->is_validated ? 'active' : 'pending'" :label="$user->is_validated ? 'Validé' : 'En attente'" />
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                        {{ optional($user->created_at)->format('d/m/Y') ?? '-' }}
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex flex-wrap justify-end gap-2">
                            <form method="POST" action="{{ route('admin.users.toggle-validation', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ui-btn-secondary">
                                    <i data-lucide="{{ $user->is_validated ? 'ban' : 'check-circle-2' }}" class="h-4 w-4" aria-hidden="true"></i>
                                    {{ $user->is_validated ? 'Désactiver' : 'Valider' }}
                                </button>
                            </form>

                            @if($canEdit)
                                <a href="{{ route('admin.users.edit', $user) }}" class="ui-btn-secondary">
                                    <i data-lucide="pencil" class="h-4 w-4" aria-hidden="true"></i>
                                    Modifier
                                </a>

                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ui-btn-danger">
                                        <i data-lucide="trash-2" class="h-4 w-4" aria-hidden="true"></i>
                                        Supprimer
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach

            <x-slot name="mobile">
                @foreach($users as $user)
                    @php
                        $roleKey = $user->isSuperAdmin() ? 'superadmin' : ($user->isAdmin() ? 'admin' : 'client');
                    @endphp
                    <article class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-avatar :name="$user->name" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                            <x-badge :status="$roleKey" />
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Client</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->client?->code_client ?? '-' }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Validation</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->is_validated ? 'Validé' : 'En attente' }}</p>
                            </div>
                            <div class="col-span-2">
                                <span class="text-gray-500 dark:text-gray-400">Créé le</span>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ optional($user->created_at)->format('d/m/Y') ?? '-' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.users.toggle-validation', $user) }}" class="flex-1">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ui-btn-secondary w-full">
                                    {{ $user->is_validated ? 'Désactiver' : 'Valider' }}
                                </button>
                            </form>

                            @if($currentUser?->isSuperAdmin())
                                <a href="{{ route('admin.users.edit', $user) }}" class="ui-btn-secondary flex-1 text-center">Modifier</a>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="flex-1" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ui-btn-danger w-full">Supprimer</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </x-slot>
        </x-data-table>
    </div>
@endsection
