@php
    $pageTitle = 'Notifications';
    $activeFilters = collect(['status', 'alert_type', 'severity', 'per_page'])->filter(fn ($key) => request()->filled($key))->count();
@endphp

@extends('layouts.app')
@section('title', $pageTitle)

@section('content')
<div class="space-y-6">
    <x-page-header
        title="Notifications"
        subtitle="Historique des alertes et notifications du portail."
        :breadcrumbs="[['label' => 'Portail'], ['label' => 'Notifications']]"
    />

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card title="Non lues" :value="number_format($unreadCount, 0, ',', ' ')" icon="bell" color="danger" />
        <x-stat-card title="Page courante" :value="number_format($notifications->count(), 0, ',', ' ')" icon="file-text" color="info" />
        <div class="ui-card flex items-center justify-between gap-4 p-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Actions</p>
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">Lecture</p>
            </div>
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="ui-btn-secondary" @disabled($unreadCount === 0)>
                    <i data-lucide="circle-check" class="h-4 w-4" aria-hidden="true"></i>
                    Tout marquer lu
                </button>
            </form>
        </div>
    </section>

    <form method="GET" action="{{ route('notifications.index') }}" class="ui-card p-4">
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <select name="status" class="ui-input" aria-label="Etat">
                    <option value="">Tous les etats</option>
                    <option value="unread" @selected(request('status') === 'unread')>Non lues</option>
                    <option value="read" @selected(request('status') === 'read')>Lues</option>
                </select>
            </div>
            <div class="lg:col-span-3">
                <select name="severity" class="ui-input" aria-label="Criticite">
                    <option value="">Toutes criticites</option>
                    @foreach(['critical' => 'Critique', 'warning' => 'Avertissement', 'success' => 'Succes', 'info' => 'Info'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-3">
                <select name="alert_type" class="ui-input" aria-label="Type">
                    <option value="">Tous types</option>
                    @foreach([
                        'unpaid_invoice' => 'Facture impayee',
                        'payment_received' => 'Paiement recu',
                        'overdue_invoice' => 'Facture en retard',
                        'invoice_update' => 'Mise a jour facture',
                        'import_completed' => 'Import termine',
                        'import_failed' => 'Import echoue',
                        'import_queued' => 'Import en file',
                        'data_gap' => 'Ecarts',
                        'payment_inconsistency' => 'Incoherence paiement',
                        'prestations_total_mismatch' => 'Total prestations',
                        'integrity_alert' => 'Integrite',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(request('alert_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-1">
                <select name="per_page" class="ui-input" aria-label="Entrees">
                    @foreach([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 lg:col-span-2">
                <button type="submit" class="ui-btn-primary flex-1">Filtrer</button>
                @if($activeFilters)
                    <a href="{{ route('notifications.index') }}" class="ui-btn-secondary" aria-label="Reinitialiser">
                        <i data-lucide="rotate-ccw" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <section class="space-y-3">
        @forelse($notifications as $notification)
            @php
                $color = $notification['color'] ?? $notification['severity'] ?? 'info';
                $tone = [
                    'success' => 'border-success-200 bg-success-50 text-success-700 dark:border-success-900/60 dark:bg-success-900/20 dark:text-success-200',
                    'warning' => 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-900/60 dark:bg-warning-900/20 dark:text-warning-200',
                    'danger' => 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-900/60 dark:bg-danger-900/20 dark:text-danger-200',
                    'critical' => 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-900/60 dark:bg-danger-900/20 dark:text-danger-200',
                    'info' => 'border-info-200 bg-info-50 text-info-700 dark:border-info-900/60 dark:bg-info-900/20 dark:text-info-200',
                ][$color] ?? 'border-info-200 bg-info-50 text-info-700 dark:border-info-900/60 dark:bg-info-900/20 dark:text-info-200';
            @endphp
            <article class="ui-card overflow-hidden {{ $notification['is_read'] ? '' : 'ring-1 ring-primary-200 dark:ring-primary-900/70' }}">
                <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border {{ $tone }}">
                        <i data-lucide="{{ $notification['icon'] ?? 'bell' }}" class="h-5 w-5" aria-hidden="true"></i>
                    </div>

                    <a href="{{ $notification['open_url'] }}" class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $notification['title'] }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold uppercase {{ $tone }}">{{ $notification['status'] }}</span>
                            @unless($notification['is_read'])
                                <span class="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-bold text-primary-700 dark:bg-primary-900/40 dark:text-primary-200">Non lue</span>
                            @endunless
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $notification['description'] }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $notification['created_at_label'] }}</span>
                            <span>{{ str_replace('_', ' ', $notification['alert_type']) }}</span>
                        </div>
                    </a>

                    <div class="flex shrink-0 gap-2 sm:flex-col">
                        <a href="{{ $notification['open_url'] }}" class="ui-btn-secondary">
                            <i data-lucide="chevron-right" class="h-4 w-4" aria-hidden="true"></i>
                            Ouvrir
                        </a>
                        @unless($notification['is_read'])
                            <form method="POST" action="{{ route('notifications.read', $notification['id']) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="ui-btn-secondary w-full">
                                    <i data-lucide="circle-check" class="h-4 w-4" aria-hidden="true"></i>
                                    Marquer lu
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            </article>
        @empty
            <x-empty-state icon="bell" title="Aucune notification" message="Les alertes apparaitront ici des leur creation." />
        @endforelse

        @if($notifications->hasPages())
            <div class="ui-card px-4 py-3">
                {{ $notifications->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
