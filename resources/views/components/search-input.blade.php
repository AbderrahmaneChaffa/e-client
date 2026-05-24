{{-- // PURPOSE: Search field with icon and debounce-friendly Alpine attributes. --}}
@props([
    'placeholder' => 'Rechercher...',
    'name' => 'search',
    'value' => null,
])

<label class="relative block">
    <span class="sr-only">{{ $placeholder }}</span>
    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
    <input
        type="search"
        name="{{ $name }}"
        value="{{ $value ?? request($name) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'ui-input pl-9']) }}
    >
</label>
