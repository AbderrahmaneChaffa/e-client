{{-- // PURPOSE: Standard empty state with icon, message and optional action. --}}
@props([
    'icon' => 'inbox',
    'title' => 'Aucune donnee',
    'message' => 'Aucun element ne correspond a votre recherche.',
    'actionLabel' => null,
    'actionRoute' => null,
])

<div {{ $attributes->merge(['class' => 'ui-card flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
        <i data-lucide="{{ $icon }}" class="h-8 w-8" aria-hidden="true"></i>
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h3>
    <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">{{ $message }}</p>
    @if($actionLabel && $actionRoute)
        <a href="{{ $actionRoute }}" class="ui-btn-primary mt-6">
            <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
            {{ $actionLabel }}
        </a>
    @endif
</div>
