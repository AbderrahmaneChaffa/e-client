{{-- // PURPOSE: Reusable animated skeleton rows for loading states. --}}
@props(['rows' => 5])

<div {{ $attributes->merge(['class' => 'space-y-3']) }} role="status" aria-label="Chargement">
    @for($i = 0; $i < (int) $rows; $i++)
        <div class="ui-card p-4">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-full skeleton-shimmer"></div>
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="h-3 w-2/5 rounded skeleton-shimmer"></div>
                    <div class="h-3 w-4/5 rounded skeleton-shimmer"></div>
                </div>
                <div class="hidden h-8 w-24 rounded md:block skeleton-shimmer"></div>
            </div>
        </div>
    @endfor
    <span class="sr-only">Chargement en cours...</span>
</div>
