@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $client = $user?->loadMissing('client')->client;
    $pageTitle = $pageTitle ?? trim($__env->yieldContent('title')) ?: config('app.name', 'E-Client');

    $navigation = [
        ['label' => 'Tableau de bord', 'route' => 'client.dashboard', 'icon' => 'layout-dashboard', 'active' => 'client.dashboard'],
        ['label' => 'Factures', 'route' => 'client.factures.index', 'icon' => 'file-text', 'active' => 'client.factures.*'],
        ['label' => 'Paiements', 'route' => 'client.paiements.index', 'icon' => 'credit-card', 'active' => 'client.paiements.*'],
        ['label' => 'Support', 'route' => 'client.support.index', 'icon' => 'message-circle', 'active' => 'client.support.*'],
        ['label' => 'Profil / paramètres', 'route' => 'profile.edit', 'icon' => 'user-cog', 'active' => 'profile.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $pageTitle) - {{ config('app.name', 'E-Client') }}</title>
    <link rel="icon" href="{{ asset('storage/Logo/logo_epo.png') }}" type="image/png">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            document.documentElement.dataset.theme = 'dark';
        }
    </script>
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[90] focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-white">
        Aller au contenu principal
    </a>

    <div
        x-data="appShell()"
        x-init="init()"
        x-on:keydown.window.escape="mobileMenuOpen = false"
        class="min-h-screen"
    >
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 border-r border-gray-200 bg-white/95 shadow-sm dark:border-gray-800 dark:bg-gray-950 md:flex md:flex-col">
            <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5 dark:border-gray-800">
                <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="h-10 w-10 object-contain">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900 dark:text-white">E-Client</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ $client?->name ?? 'Espace client' }}
                    </p>
                </div>
            </div>

            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <x-avatar :name="$user?->name" size="md" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ $client?->code_client ?? 'Client' }}
                        </p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Navigation client">
                @foreach($navigation as $item)
                    @if(Route::has($item['route']))
                        <a
                            href="{{ route($item['route']) }}"
                            @class([
                                'group flex items-center gap-3 rounded-lg border-l-4 px-3 py-2.5 text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-600',
                                'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' => request()->routeIs($item['active']),
                                'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' => ! request()->routeIs($item['active']),
                            ])
                            @if(request()->routeIs($item['active'])) aria-current="page" @endif
                        >
                            <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-gray-200 p-3 dark:border-gray-800">
                <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Confirmer la deconnexion ?');">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-danger-600 transition hover:bg-danger-50 dark:text-danger-300 dark:hover:bg-danger-900/20">
                        <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                        Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        <div
            x-show="mobileMenuOpen"
            x-cloak
            class="fixed inset-0 z-50 md:hidden"
            aria-hidden="false"
        >
            <div class="absolute inset-0 bg-gray-950/55 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>
            <aside
                x-ref="mobileSidebar"
                class="relative flex h-full w-80 max-w-[85vw] flex-col bg-white shadow-2xl dark:bg-gray-950"
                role="dialog"
                aria-modal="true"
                aria-label="Navigation client"
                @keydown.tab="trapMobileMenuFocus($event)"
            >
                <div class="flex h-16 items-center justify-between border-b border-gray-200 px-5 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="h-9 w-9 object-contain">
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100">E-Client</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $client?->code_client ?? 'Client' }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-ref="sidebarClose"
                        class="ui-icon-btn"
                        @click="mobileMenuOpen = false"
                        aria-label="Fermer le menu"
                    >
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$user?->name" size="md" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user?->email }}</p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Navigation client mobile">
                    @foreach($navigation as $item)
                        @if(Route::has($item['route']))
                            <a
                                href="{{ route($item['route']) }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-primary-600 {{ request()->routeIs($item['active']) ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'text-gray-600 dark:text-gray-300' }}"
                                @click="mobileMenuOpen = false"
                            >
                                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </nav>
            </aside>
        </div>

        <div class="min-h-screen md:ml-64">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-950/90">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        class="ui-icon-btn md:hidden"
                        @click="mobileMenuOpen = true; $nextTick(() => $refs.sidebarClose?.focus())"
                        aria-label="Ouvrir la navigation"
                        :aria-expanded="mobileMenuOpen.toString()"
                    >
                        <i data-lucide="menu" class="h-4 w-4" aria-hidden="true"></i>
                    </button>

                    <div class="hidden min-w-0 md:block">
                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $pageTitle }}</p>
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                            {{ now()->locale('fr')->translatedFormat('l d F Y') }}
                        </p>
                    </div>

                    <div class="flex-1"></div>

                    <button type="button" class="ui-icon-btn" @click="toggleTheme()" :aria-label="isDark ? 'Activer le mode clair' : 'Activer le mode sombre'">
                        <i data-lucide="sun" class="hidden h-4 w-4 dark:block" aria-hidden="true"></i>
                        <i data-lucide="moon" class="h-4 w-4 dark:hidden" aria-hidden="true"></i>
                    </button>

                    <x-notification-center />

                    <div class="relative" x-data="{ open: false }">
                        <button
                            type="button"
                            @click="open = ! open"
                            class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-950"
                            :aria-expanded="open.toString()"
                            aria-haspopup="true"
                        >
                            <x-avatar :name="$user?->name" size="sm" />
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition
                            @click.outside="open = false"
                            class="absolute right-0 mt-3 w-64 rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user?->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                <i data-lucide="user-cog" class="h-4 w-4" aria-hidden="true"></i>
                                Profil / paramètres
                            </a>
                            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Confirmer la deconnexion ?');">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-danger-600 hover:bg-danger-50 dark:text-danger-300 dark:hover:bg-danger-900/20">
                                    <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                                    Déconnexion
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="main-content" class="page-fade px-4 py-6 pb-24 sm:px-6 lg:px-8 md:pb-8">
                <div class="mx-auto max-w-7xl">
                    @if ($errors->any())
                        <div class="mb-6 rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-800 dark:border-danger-800 dark:bg-danger-900/20 dark:text-danger-200" role="alert">
                            <div class="flex items-start gap-3">
                                <i data-lucide="circle-alert" class="mt-0.5 h-5 w-5" aria-hidden="true"></i>
                                <div>
                                    <p class="font-semibold">Veuillez corriger les erreurs ci-dessous.</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </div>
            </main>
        </div>

        <x-flash-message />
    </div>

    @stack('scripts')
    @yield('extra-js')
</body>
</html>
