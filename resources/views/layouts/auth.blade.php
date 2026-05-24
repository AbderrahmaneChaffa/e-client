@php
    $pageTitle = $pageTitle ?? trim($__env->yieldContent('title')) ?: config('app.name', 'E-Client');
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
</head>
<body class="min-h-full bg-gray-50 font-sans text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(79,70,229,0.14),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(14,165,233,0.12),transparent_36%)]"></div>
        <svg class="absolute inset-0 h-full w-full opacity-[0.08] dark:opacity-[0.12]" aria-hidden="true">
            <defs>
                <pattern id="auth-grid" width="44" height="44" patternUnits="userSpaceOnUse">
                    <path d="M44 0H0V44" fill="none" stroke="currentColor" stroke-width="1" class="text-gray-500" />
                    <path d="M22 0V44M0 22H44" fill="none" stroke="currentColor" stroke-width="0.5" class="text-primary-600" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#auth-grid)" />
        </svg>

        <main class="relative grid min-h-screen grid-cols-1 lg:grid-cols-2">
            <section class="hidden items-center justify-center px-12 lg:flex">
                <div class="max-w-md">
                    <div class="mb-8 flex items-center gap-3">
                        <img src="{{ asset('storage/Logo/logo_epo.png') }}" alt="Logo EPO" class="h-14 w-14 object-contain">
                        <div>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">E-Client</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Portail facture et paiement</p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-white/50 bg-white/60 p-8 shadow-soft backdrop-blur dark:border-gray-800 dark:bg-gray-900/60">
                        <i data-lucide="ship" class="mb-6 h-12 w-12 text-primary-600 dark:text-primary-300" aria-hidden="true"></i>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Gestion fluide des factures portuaires.</h1>
                        <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-400">
                            Suivez vos factures, paiements et imports ERP BIG dans une interface claire, rapide et securisee.
                        </p>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center px-4 py-10 sm:px-6 lg:px-12">
                <div class="w-full max-w-md">
                    @yield('content')
                    {{ $slot ?? '' }}
                </div>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
</body>
</html>
