@php
    $status = $facture->verification_status ?? 'ok';
    $flags = collect($facture->verification_flags ?? []);
@endphp

@if($status === 'critical')
    <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-2.5 py-1 rounded-full text-xs font-semibold"
        title="{{ $flags->pluck('label')->join(' | ') }}">
        <i class="fa-solid fa-circle-xmark"></i>
        Erreur
    </span>
@elseif($status === 'warning')
    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full text-xs font-semibold"
        title="{{ $flags->pluck('label')->join(' | ') }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Avertissement
    </span>
@else
    <span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2.5 py-1 rounded-full text-xs font-semibold">
        <i class="fa-solid fa-check"></i>
        OK
    </span>
@endif

@if($flags->isNotEmpty())
    <div class="mt-1 flex flex-wrap gap-1 justify-center">
        @foreach($flags->take(2) as $flag)
            <span class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                {{ $flag['label'] ?? $flag['code'] ?? 'Verification' }}
            </span>
        @endforeach
    </div>
@endif
