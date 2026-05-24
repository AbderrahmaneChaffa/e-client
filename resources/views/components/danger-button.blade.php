{{-- // PURPOSE: Destructive action button with danger styling. --}}
<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ui-btn-danger']) }}>
    {{ $slot }}
</button>
