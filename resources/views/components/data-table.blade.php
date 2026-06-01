{{-- // PURPOSE: Responsive table shell with desktop table and mobile card-list fallback. --}}
@props([
    'columns' => [],
    'rows' => [],
    'filters' => [],
    'sortable' => true,
    'emptyTitle' => 'Aucune donnée',
    'emptyMessage' => 'Modifiez les filtres ou ajoutez un nouvel élément.',
])

@php
    $collection = $rows instanceof \Illuminate\Pagination\AbstractPaginator ? $rows->getCollection() : collect($rows);
    $currentSort = (string) request('sort');
    $currentDirection = request('direction', request('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
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
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-5 inline-flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-300">
                    <i data-lucide="inbox" class="h-8 w-8" aria-hidden="true"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ $emptyTitle }}</h3>
                <p class="mt-2 max-w-md text-sm text-gray-600 dark:text-gray-400">{{ $emptyMessage }}</p>
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
                                @php
                                    $isActive = $currentSort === $key;
                                    $nextDirection = $isActive && $currentDirection === 'asc' ? 'desc' : 'asc';
                                    $query = request()->except(['page', 'dir']);
                                    $query['sort'] = $key;
                                    $query['direction'] = $nextDirection;
                                    $sortUrl = url()->current().'?'.http_build_query($query);
                                    $ariaSort = $isActive ? ($currentDirection === 'asc' ? 'ascending' : 'descending') : 'none';
                                @endphp
                                <th scope="col" aria-sort="{{ $sortable && $key ? $ariaSort : 'none' }}" class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                    @if($sortable && $key)
                                        <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1 {{ $isActive ? 'text-primary-700 dark:text-primary-300' : 'hover:text-primary-600 dark:hover:text-primary-300' }}">
                                            {{ $label }}
                                            <i data-lucide="{{ $isActive ? ($currentDirection === 'asc' ? 'arrow-up' : 'arrow-down') : 'arrow-up-down' }}" class="h-3.5 w-3.5" aria-hidden="true"></i>
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
