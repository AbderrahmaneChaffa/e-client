{{-- // PURPOSE: Accessible Alpine modal with focus trap compatibility and scale/fade transitions. --}}
@props([
    'id' => null,
    'name' => null,
    'title' => null,
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    $modalId = $id ?? $name ?? 'modal';
    $maxWidthClass = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '4xl' => 'sm:max-w-4xl',
    ][$maxWidth] ?? 'sm:max-w-2xl';
@endphp

<div
    x-data="{
        show: @js($show),
        focusables() {
            return [...$el.querySelectorAll('a, button, input:not([type=hidden]), textarea, select, details, [tabindex]:not([tabindex=-1])')]
                .filter(el => ! el.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % this.focusables().length },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
        close() { this.show = false; this.$dispatch('close-modal', '{{ $modalId }}') },
    }"
    x-on:open-modal.window="$event.detail === '{{ $modalId }}' ? show = true : null"
    x-on:close-modal.window="$event.detail === '{{ $modalId }}' ? show = false : null"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[80] overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $modalId }}-title"
>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm" @click="close()"></div>

    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        class="relative mb-6 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800 sm:mx-auto {{ $maxWidthClass }}"
        x-on:keydown.tab.prevent="$event.shiftKey ? prevFocusable()?.focus() : nextFocusable()?.focus()"
    >
        @if($title)
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h2 id="{{ $modalId }}-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $title }}</h2>
                <button type="button" class="ui-icon-btn" @click="close()" aria-label="Fermer">
                    <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                </button>
            </div>
            <div class="p-5">
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
