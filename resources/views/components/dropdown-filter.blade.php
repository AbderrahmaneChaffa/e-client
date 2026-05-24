{{-- // PURPOSE: Dropdown checkbox/select filter for table toolbars. --}}
@props([
    'label' => 'Filtre',
    'name' => 'status',
    'options' => [],
    'multiple' => false,
])

@php
    $selected = (array) request($name, []);
    if (! $multiple && request()->filled($name)) {
        $selected = [request($name)];
    }
    $activeCount = count(array_filter($selected, fn ($value) => $value !== null && $value !== ''));
@endphp

<div x-data="{ open: false }" class="relative">
    <button type="button" @click="open = ! open" class="ui-btn-secondary w-full justify-between sm:w-auto" :aria-expanded="open.toString()">
        <span class="inline-flex items-center gap-2">
            <i data-lucide="sliders-horizontal" class="h-4 w-4" aria-hidden="true"></i>
            {{ $label }}
        </span>
        @if($activeCount > 0)
            <span class="rounded-full bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $activeCount }}</span>
        @endif
        <i data-lucide="chevron-down" class="h-4 w-4" aria-hidden="true"></i>
    </button>

    <div x-show="open" x-cloak @click.outside="open = false"
        x-transition
        class="absolute left-0 z-40 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
        @if($multiple)
            <div class="max-h-64 space-y-1 overflow-y-auto">
                @foreach($options as $value => $optionLabel)
                    <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked(in_array((string) $value, array_map('strval', $selected), true)) class="rounded border-gray-300 text-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-900">
                        <span>{{ $optionLabel }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <select name="{{ $name }}" class="ui-input">
                <option value="">Tous</option>
                @foreach($options as $value => $optionLabel)
                    <option value="{{ $value }}" @selected(request($name) == $value)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @endif
    </div>
</div>
