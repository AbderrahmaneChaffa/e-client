{{-- // PURPOSE: Semantic status badge used by tables, cards and timelines. --}}
@props(['status' => 'default', 'label' => null])

@php
    $key = strtolower((string) $status);
    $map = [
        'active' => ['label' => 'Actif', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-300', 'icon' => 'check-circle-2'],
        'inactive' => ['label' => 'Inactif', 'class' => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-700 dark:text-slate-200', 'icon' => 'circle'],
        'pending' => ['label' => 'En attente', 'class' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-300', 'icon' => 'clock-3'],
        'processing' => ['label' => 'En cours', 'class' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-900/30 dark:text-info-300', 'icon' => 'loader-circle'],
        'completed' => ['label' => 'Termine', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-300', 'icon' => 'check-circle-2'],
        'failed' => ['label' => 'Echec', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'x-circle'],
        'cancelled' => ['label' => 'Annule', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'ban'],
        'annulee' => ['label' => 'Annulee', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'ban'],
        'paye' => ['label' => 'Payee', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-300', 'icon' => 'check-circle-2'],
        'payee' => ['label' => 'Payee', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-300', 'icon' => 'check-circle-2'],
        'impaye' => ['label' => 'Impayee', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'alert-circle'],
        'warning' => ['label' => 'A verifier', 'class' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-300', 'icon' => 'triangle-alert'],
        'critical' => ['label' => 'Critique', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'octagon-alert'],
        'ok' => ['label' => 'OK', 'class' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-900/30 dark:text-success-300', 'icon' => 'shield-check'],
        'new' => ['label' => 'Nouveau', 'class' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-900/30 dark:text-info-300', 'icon' => 'plus-circle'],
        'modified' => ['label' => 'Modifie', 'class' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-900/30 dark:text-warning-300', 'icon' => 'pencil'],
        'missing' => ['label' => 'Manquant', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'circle-off'],
        'inconsistent' => ['label' => 'Incoherent', 'class' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-900/30 dark:text-danger-300', 'icon' => 'octagon-alert'],
        'admin' => ['label' => 'Admin', 'class' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-900/30 dark:text-primary-300', 'icon' => 'shield'],
        'client' => ['label' => 'Client', 'class' => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-700 dark:text-slate-200', 'icon' => 'user'],
    ];
    $badge = $map[$key] ?? ['label' => ucfirst($key), 'class' => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-700 dark:text-slate-200', 'icon' => 'circle'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset '.$badge['class']]) }}>
    <i data-lucide="{{ $badge['icon'] }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
    {{ $label ?? $badge['label'] }}
</span>
