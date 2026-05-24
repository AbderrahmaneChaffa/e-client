{{-- // PURPOSE: User or entity avatar with initials fallback. --}}
@props([
    'name' => '',
    'size' => 'md',
    'src' => null,
])

@php
    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-16 w-16 text-xl',
    ];
    $class = $sizes[$size] ?? $sizes['md'];
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}" loading="lazy" {{ $attributes->merge(['class' => $class.' rounded-full object-cover ring-2 ring-white dark:ring-gray-800']) }}>
@else
    <span {{ $attributes->merge(['class' => $class.' inline-flex items-center justify-center rounded-full bg-primary-600 font-bold uppercase text-white ring-2 ring-white dark:ring-gray-800']) }}>
        {{ $initials ?: '?' }}
    </span>
@endif
