{{-- // PURPOSE: Responsive table shell with desktop table and mobile card-list fallback. --}}
@props([
    'columns' => [],
    'rows' => [],
    'filters' => [],
    'sortable' => true,
    'emptyTitle' => 'Aucune donnee',
    'emptyMessage' => 'Modifiez les filtres ou ajoutez un nouvel element.',
])

@php
    $collection = $rows instanceof \Illuminate\Pagination\AbstractPaginator ? $rows->getCollection() : collect($rows);
@endphp

<div {{ $attributes->merge(['class' => 'ui-card overflow-hidden']) }} x-data="{ busy: false }">
    @if($filters)
        <div class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
            {{ $filters }}
        </div>
    @endif

    <div class="relative">
        <div x-show="busy" x-cloak class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-gray-900/70">
            <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow dark:bg-gray-800 dark:text-gray-200">
                <i data-lucide="loader-circle" class="h-4 w-4 animate-spin" aria-hidden="true"></i>
                Filtrage...
            </span>
        </div>

        @if($collection->isEmpty())
            <div class="p-4">
                <x-empty-state icon="inbox" :title="$emptyTitle" :message="$emptyMessage" />
            </div>
        @else
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/70">
                        <tr>
                            @foreach($columns as $column)
                                @php
                                    $key = is_array($column) ? ($column['key'] ?? '') : $column;
                                    $label = is_array($column) ? ($column['label'] ?? ucfirst($key)) : ucfirst($column);
                                @endphp
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                    @if($sortable && $key)
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'dir' => request('dir') === 'asc' ? 'desc' : 'asc']) }}" class="inline-flex items-center gap-1 hover:text-primary-600 dark:hover:text-primary-300">
                                            {{ $label }}
                                            <i data-lucide="{{ request('sort') === $key ? (request('dir') === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        </a>
                                    @else
                                        {{ $label }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        {{ $slot }}
                    </tbody>
                </table>
            </div>

            @if(isset($mobile))
                <div class="space-y-3 p-4 md:hidden">
                    {{ $mobile }}
                </div>
            @endif
        @endif
    </div>

    @if($rows instanceof \Illuminate\Pagination\AbstractPaginator)
        <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            {{ $rows->appends(request()->query())->links() }}
        </div>
    @endif
</div>
