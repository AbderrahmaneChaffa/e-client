<!DOCTYPE html>
<html lang="fr" x-data="setup()" :class="{ 'dark': isDarkMode }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPO - Administration</title>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .active-link {
            @apply bg-indigo-600 text-white shadow-md shadow-indigo-200 dark:shadow-indigo-900/20;
        }
    </style>
</head>

<body class="bg-gray-50 dark:bg-slate-950 font-sans antialiased text-slate-900 dark:text-slate-100 transition-colors duration-300">
    <div class="flex h-screen overflow-hidden">

        <aside
            class="sidebar-transition bg-slate-900 text-white flex-shrink-0 z-20 shadow-xl flex flex-col"
            :class="sidebarOpen ? 'w-64' : 'w-20'">

            <div class="h-20 flex items-center px-4 border-b border-slate-800 overflow-hidden">
                <img src="{{ asset('storage/Logo/petit taille.png') }}" class="h-10 w-10 min-w-[40px] object-contain">
                <span x-show="sidebarOpen" x-transition.opacity class="ml-3 font-bold text-xl tracking-tight whitespace-nowrap">
                    E-Client <span class="text-indigo-400">EPO</span>
                </span>
            </div>

            <nav class="flex-1 mt-6 space-y-2 px-3 overflow-y-auto">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center py-3 px-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'active-link' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chart-line w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3 font-medium">Dashboard</span>
                </a>

                <a href="{{ route('admin.clients.index') }}"
                    class="flex items-center py-3 px-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.clients.*') ? 'active-link' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-users w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3 font-medium">Clients</span>
                </a>

                <a href="{{ route('admin.factures.index') }}"
                    class="flex items-center py-3 px-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.factures.*') ? 'active-link' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-file-invoice w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3 font-medium">Factures</span>
                </a>
                <a href="{{ route('admin.paiements.index') }}"
                    class="flex items-center py-3 px-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.paiements.*') ? 'active-link' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-university w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3 font-medium">Paiements</span>
                </a>
                <a href="{{ route('admin.imports.index') }}"
                    class="flex items-center py-3 px-3 rounded-xl transition-all duration-200 group {{ request()->routeIs('admin.imports.*') ? 'active-link' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-cloud-arrow-up w-6 text-center text-lg"></i>
                    <span x-show="sidebarOpen" class="ml-3 font-medium">Import Excel</span>
                </a>
            </nav>

            <button @click="sidebarOpen = !sidebarOpen" class="p-4 border-t border-slate-800 text-slate-400 hover:text-white text-center">
                <i class="fa-solid" :class="sidebarOpen ? 'fa-angles-left' : 'fa-angles-right'"></i>
            </button>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            <header class="h-20 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 flex items-center justify-between px-8 z-10 transition-colors">

                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white">Admin Panel</h2>
                </div>

                <div class="flex items-center gap-6">
                    <button @click="toggleTheme" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-indigo-300 hover:ring-2 ring-indigo-500 transition-all">
                        <i class="fa-solid" :class="isDarkMode ? 'fa-sun' : 'fa-moon'"></i>
                    </button>

                    <div class="hidden lg:block text-right border-l pl-6 border-slate-200 dark:border-slate-700">
                        <p class="text-sm font-bold text-slate-700 dark:text-white" id="current-date">{{ now()->format('d M Y') }}</p>
                        <p class="text-xs text-slate-500" id="current-time">{{ now()->format('H:i') }}</p>
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-3 focus:outline-none">
                            <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg ring-2 ring-indigo-500/20">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        </button>

                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:leave="transition ease-in duration-75"
                            class="absolute right-0 mt-3 w-56 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100 dark:border-slate-700 mb-2">
                                <p class="text-sm font-bold dark:text-white">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500 italic">Administrateur</p>
                            </div>
                            <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-700 transition"><i class="fa-solid fa-user-gear mr-3"></i> Profil</a>
                            <form action="/logout" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition"><i class="fa-solid fa-power-off mr-3"></i> Déconnexion</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-950 p-6 lg:p-10">
                @if(session('success'))
                <div class="max-w-4xl mx-auto mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 rounded-2xl flex items-center shadow-sm">
                    <i class="fa-solid fa-circle-check mr-3 text-xl"></i>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                @endif

                <div class="max-w-7xl mx-auto">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <script>
        function setup() {
            return {
                // Initialisation intelligente : vérifie le localStorage OU la préférence système
                isDarkMode: localStorage.getItem('dark') === 'true' ||
                    (!('dark' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),

                sidebarOpen: true,

                toggleTheme() {
                    this.isDarkMode = !this.isDarkMode;
                    localStorage.setItem('dark', this.isDarkMode);

                    // On force l'application immédiate de la classe sur l'élément HTML
                    if (this.isDarkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },

                init() {
                    // S'assure que la classe est correcte au chargement de la page
                    if (this.isDarkMode) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        }

        function updateTime() {
            const now = new Date();
            const timeEl = document.getElementById('current-time');
            if (timeEl) timeEl.textContent = now.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        setInterval(updateTime, 1000);
    </script>
</body>

</html>