{{-- // PURPOSE: Secondary neutral action button. --}}
<button {{ $attributes->merge(['type' => 'button', 'class' => 'ui-btn-secondary']) }}>
    {{ $slot }}
</button>
