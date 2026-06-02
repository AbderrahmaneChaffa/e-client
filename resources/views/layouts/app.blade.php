@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    $pageTitle = $pageTitle ?? trim($__env->yieldContent('title')) ?: config('app.name', 'E-Client');

    $adminNav = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Clients', 'route' => 'admin.clients.index', 'icon' => 'users', 'active' => 'admin.clients.*'],
        ['label' => 'Factures', 'route' => 'admin.factures.index', 'icon' => 'file-text', 'active' => 'admin.factures.*'],
        ['label' => 'Paiements', 'route' => 'admin.paiements.index', 'icon' => 'credit-card', 'active' => 'admin.paiements.*'],
        ['label' => 'Imports', 'route' => 'admin.imports.index', 'icon' => 'upload-cloud', 'active' => 'admin.imports.*'],
    ];

    $clientNav = [
        ['label' => 'Accueil', 'route' => 'client.dashboard', 'icon' => 'home', 'active' => 'client.dashboard'],
        ['label' => 'Factures', 'route' => 'client.factures.index', 'icon' => 'file-text', 'active' => 'client.factures.*'],
        ['label' => 'Paiements', 'route' => 'client.paiements.index', 'icon' => 'credit-card', 'active' => 'client.paiements.*'],
        ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'user-cog', 'active' => 'profile.*'],
    ];

    $activeNav = $isAdmin ? $adminNav : $clientNav;
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
    <style media="print">
    @page { margin: 1cm; size: A4 landscape; }
    body { font-size: 11pt; -webkit-print-color-adjust: exact; }
    .no-print, .ui-btn, form, .pagination { display: none !important; }
    .ui-card { box-shadow: none !important; border: 1px solid #ddd !important; }
    table { width: 100% !important; border-collapse: collapse; }
    th, td { border: 1px solid #ccc !important; padding: 4px !important; }
    .bg-primary-50\/30 { background: #f0f9ff !important; }
</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[90] focus:rounded-lg focus:bg-primary-600 focus:px-4 focus:py-2 focus:text-white">
        Aller au contenu principal
    </a>

    <div
        x-data="appShell()"
        x-init="init()"
        x-on:keydown.window.ctrl.k.prevent="$refs.globalSearch?.focus()"
        class="min-h-full"
    >
        @if($isAdmin)
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-60 border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-950 md:flex md:flex-col">
                <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5 dark:border-gray-800">
                    <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="h-9 w-9 object-contain">
                    <div>
                        <p class="text-sm font-bold text-gray-900 dark:text-white">E-Client</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Administration</p>
                    </div>
                </div>

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$user?->name" size="md" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Administrateur</p>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4" aria-label="Navigation admin">
                    @foreach($adminNav as $item)
                        @if(Route::has($item['route']))
                            <a href="{{ route($item['route']) }}"
                                class="group flex items-center gap-3 rounded-lg border-l-4 px-3 py-2.5 text-sm font-medium transition-colors duration-200 {{ request()->routeIs($item['active']) ? 'border-primary-600 bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'border-transparent text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100' }}">
                                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                                <span>{{ $item['label'] }}</span>
                                @if($item['route'] === 'admin.imports.index')
                                    <span class="ml-auto rounded-full bg-warning-100 px-2 py-0.5 text-[10px] font-bold text-warning-700 dark:bg-warning-900/40 dark:text-warning-200">ERP</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </nav>

                <div class="border-t border-gray-200 p-3 dark:border-gray-800">
                    <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Confirmer la deconnexion ?');">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-danger-600 transition hover:bg-danger-50 dark:text-danger-300 dark:hover:bg-danger-900/20">
                            <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                            Deconnexion
                        </button>
                    </form>
                </div>
            </aside>

            <div x-show="mobileMenuOpen" x-cloak class="fixed inset-0 z-50 md:hidden">
                <div class="absolute inset-0 bg-gray-950/50" @click="mobileMenuOpen = false"></div>
                <aside class="relative flex h-full w-72 flex-col bg-white shadow-2xl dark:bg-gray-950">
                    <div class="flex h-16 items-center justify-between border-b border-gray-200 px-5 dark:border-gray-800">
                        <span class="font-bold">Menu admin</span>
                        <button type="button" class="ui-icon-btn" @click="mobileMenuOpen = false" aria-label="Fermer le menu">
                            <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    </div>
                    <nav class="space-y-1 p-3">
                        @foreach($adminNav as $item)
                            @if(Route::has($item['route']))
                                <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'text-gray-600 dark:text-gray-300' }}">
                                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                                    {{ $item['label'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </aside>
            </div>
        @endif

        <div class="{{ $isAdmin ? 'md:pl-60' : '' }}">
            <header class="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-950/90">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    @if($isAdmin)
                        <button type="button" class="ui-icon-btn md:hidden" @click="mobileMenuOpen = true" aria-label="Ouvrir le menu">
                            <i data-lucide="menu" class="h-4 w-4" aria-hidden="true"></i>
                        </button>
                    @else
                        <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3">
                            <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="h-9 w-9 object-contain">
                            <span class="hidden text-sm font-bold text-gray-900 dark:text-white sm:block">E-Client</span>
                        </a>
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="mx-auto max-w-xl">
                            {{-- <label class="relative hidden md:block">
                                <span class="sr-only">Recherche globale</span>
                                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                                <input x-ref="globalSearch" type="search" placeholder="Recherche globale (Ctrl+K)" class="ui-input h-10 pl-9">
                            </label> --}}
                        </div>
                    </div>

                    @if(! $isAdmin)
                        <nav class="hidden items-center gap-1 md:flex" aria-label="Navigation client">
                            @foreach($clientNav as $item)
                                @if(Route::has($item['route']))
                                    <a href="{{ route($item['route']) }}" class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs($item['active']) ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-200' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endif
                            @endforeach
                        </nav>
                    @endif

                    <button type="button" class="ui-icon-btn" @click="toggleTheme()" :aria-label="isDark ? 'Activer le mode clair' : 'Activer le mode sombre'">
                        <i data-lucide="sun" class="hidden h-4 w-4 dark:block" aria-hidden="true"></i>
                        <i data-lucide="moon" class="h-4 w-4 dark:hidden" aria-hidden="true"></i>
                    </button>

                    <x-notification-center />

                    <div class="relative" x-data="{ open: false }">
                        <button type="button" @click="open = ! open" class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:focus:ring-offset-gray-950">
                            <x-avatar :name="$user?->name" size="sm" />
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition class="absolute right-0 mt-3 w-64 rounded-lg border border-gray-200 bg-white p-2 shadow-xl dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-100 px-3 py-2 dark:border-gray-700">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user?->email }}</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                <i data-lucide="user-cog" class="h-4 w-4" aria-hidden="true"></i>
                                Profil
                            </a>
                            <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Confirmer la deconnexion ?');">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-danger-600 hover:bg-danger-50 dark:text-danger-300 dark:hover:bg-danger-900/20">
                                    <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                                    Deconnexion
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

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-950/95 md:hidden" aria-label="Navigation mobile">
            <div class="grid grid-cols-4">
                @foreach(array_slice($activeNav, 0, 4) as $item)
                    @if(Route::has($item['route']))
                        <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1 px-2 py-2 text-[11px] font-medium {{ request()->routeIs($item['active']) ? 'text-primary-600 dark:text-primary-300' : 'text-gray-500 dark:text-gray-400' }}">
                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5" aria-hidden="true"></i>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </nav>

        <x-flash-message />
    </div>

    @stack('scripts')
    @yield('extra-js')
</body>
</html>
