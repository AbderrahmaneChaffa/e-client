{{-- // PURPOSE: Compact KPI card with icon, value, color and trend metadata. --}}
@props([
    'title' => '',
    'value' => '',
    'icon' => 'activity',
    'trend' => null,
    'trendValue' => null,
    'color' => 'primary',
])

@php
    $palette = [
        'primary' => ['bg' => 'bg-primary-50 dark:bg-primary-900/30', 'text' => 'text-primary-600 dark:text-primary-300'],
        'success' => ['bg' => 'bg-success-50 dark:bg-success-900/30', 'text' => 'text-success-600 dark:text-success-300'],
        'warning' => ['bg' => 'bg-warning-50 dark:bg-warning-900/30', 'text' => 'text-warning-600 dark:text-warning-300'],
        'danger' => ['bg' => 'bg-danger-50 dark:bg-danger-900/30', 'text' => 'text-danger-600 dark:text-danger-300'],
        'info' => ['bg' => 'bg-info-50 dark:bg-info-900/30', 'text' => 'text-info-600 dark:text-info-300'],
        'slate' => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-200'],
    ];
    $tone = $palette[$color] ?? $palette['primary'];
    $trendUp = in_array($trend, ['up', '+', 'positive'], true);
    $trendDown = in_array($trend, ['down', '-', 'negative'], true);
@endphp

<article {{ $attributes->merge(['class' => 'ui-card p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-soft']) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
            <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 tabular-nums">{{ $value }}</p>
        </div>
        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $tone['bg'] }} {{ $tone['text'] }}">
            <i data-lucide="{{ $icon }}" class="h-5 w-5" aria-hidden="true"></i>
        </span>
    </div>

    @if($trend || $trendValue)
        <div class="mt-4 inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-semibold"
            title="vs periode precedente"
            class="{{ $trendDown ? 'bg-danger-50 text-danger-700 dark:bg-danger-900/30 dark:text-danger-300' : 'bg-success-50 text-success-700 dark:bg-success-900/30 dark:text-success-300' }}">
            <i data-lucide="{{ $trendDown ? 'arrow-down-right' : 'arrow-up-right' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
            <span>{{ $trendValue ?? $trend }}</span>
        </div>
    @endif
</article>
