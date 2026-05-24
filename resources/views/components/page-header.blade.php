{{-- // PURPOSE: Consistent page title, subtitle and breadcrumb header. --}}
@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<header {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if($breadcrumbs)
            <nav class="mb-2 flex flex-wrap items-center gap-1 text-xs text-gray-500 dark:text-gray-400" aria-label="Fil d'Ariane">
                @foreach($breadcrumbs as $crumb)
                    @if(! $loop->first)
                        <i data-lucide="chevron-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    @endif
                    @if(is_array($crumb) && isset($crumb['url']) && ! $loop->last)
                        <a href="{{ $crumb['url'] }}" class="hover:text-primary-600 dark:hover:text-primary-300">{{ $crumb['label'] }}</a>
                    @else
                        <span class="{{ $loop->last ? 'font-medium text-gray-700 dark:text-gray-200' : '' }}">
                            {{ is_array($crumb) ? ($crumb['label'] ?? '') : $crumb }}
                        </span>
                    @endif
                @endforeach
            </nav>
        @endif
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl">{{ $title }}</h1>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $subtitle }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</header>
