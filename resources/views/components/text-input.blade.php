{{-- // PURPOSE: Standard text input with shared design-system styling. --}}
@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'ui-input']) }}>
