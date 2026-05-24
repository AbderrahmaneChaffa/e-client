{{-- // PURPOSE: Verification status badge for invoice integrity flags. --}}
@php
    $status = $facture->verification_status ?? 'ok';
    $flags = collect($facture->verification_flags ?? []);
@endphp

<div class="inline-flex flex-col items-start gap-1">
    <x-badge :status="$status" :title="$flags->pluck('label')->join(' | ')" />
    @if($flags->isNotEmpty())
        <div class="flex flex-wrap gap-1">
            @foreach($flags->take(2) as $flag)
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ $flag['label'] ?? $flag['code'] ?? 'Verification' }}
                </span>
            @endforeach
        </div>
    @endif
</div>
