{{-- // PURPOSE: Accessible date range picker using native inputs and GET-friendly names. --}}
@props([
    'from' => null,
    'to' => null,
    'fromName' => 'date_from',
    'toName' => 'date_to',
])

<div {{ $attributes->merge(['class' => 'grid grid-cols-1 gap-3 sm:grid-cols-2']) }}>
    <div>
        <label for="{{ $fromName }}" class="ui-label mb-1">Du</label>
        <input id="{{ $fromName }}" type="date" name="{{ $fromName }}" value="{{ $from ?? request($fromName) }}" class="ui-input">
    </div>
    <div>
        <label for="{{ $toName }}" class="ui-label mb-1">Au</label>
        <input id="{{ $toName }}" type="date" name="{{ $toName }}" value="{{ $to ?? request($toName) }}" class="ui-input">
    </div>
</div>
