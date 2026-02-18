<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPO - Administration</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-left: 4px solid #a78bfa;
            padding-left: calc(1.5rem - 4px);
        }
        
        .sidebar-nav a:hover {
            background-color: rgba(102, 126, 234, 0.15);
            border-left: 4px solid #667eea;
            padding-left: calc(1.5rem - 4px);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--color-start), var(--color-end));
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white flex-shrink-0 shadow-lg">
            <!-- Logo -->
            <div class="p-6 text-center border-b border-slate-700">
                <div class="flex items-center justify-center mb-3">
                    <img src="{{ asset('storage/Logo/petit taille.png') }}" alt="Logo EPO" class="h-12 w-auto">
                </div>
                <h1 class="font-bold text-lg text-white">E-Client EPO</h1>
                <p class="text-xs text-slate-400 mt-1">Administration</p>
            </div>

            <!-- Navigation -->
            <nav class="mt-8 space-y-1 px-3 sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'active' : 'text-slate-300 hover:bg-slate-700/50' }} transition">
                    <i class="fa-solid fa-chart-line mr-3 w-5"></i>
                    <span class="font-medium">Dashboard</span>
                </a>

                <a href="{{route('admin.clients.index')}}" class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.clients.*') ? 'active' : 'text-slate-300 hover:bg-slate-700/50' }} transition">
                    <i class="fa-solid fa-users mr-3 w-5"></i>
                    <span class="font-medium">Clients</span>
                </a>

                <a href="{{route('admin.factures.index')}}" class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.factures.*') ? 'active' : 'text-slate-300 hover:bg-slate-700/50' }} transition">
                    <i class="fa-solid fa-file-invoice mr-3 w-5"></i>
                    <span class="font-medium">Factures</span>
                </a>

                <a href="{{route('admin.paiements.index')}}" class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.paiements.*') ? 'active' : 'text-slate-300 hover:bg-slate-700/50' }} transition">
                    <i class="fa-solid fa-credit-card mr-3 w-5"></i>
                    <span class="font-medium">Paiements</span>
                </a>

                <a href="{{route('admin.imports.index')}}" class="flex items-center py-3 px-4 rounded-lg {{ request()->routeIs('admin.imports.*') ? 'active' : 'text-slate-300 hover:bg-slate-700/50' }} transition">
                    <i class="fa-solid fa-upload mr-3 w-5"></i>
                    <span class="font-medium">Import Excel</span>
                </a>

                <hr class="my-4 border-slate-700">

                <a href="#" class="flex items-center py-3 px-4 rounded-lg text-slate-300 hover:bg-slate-700/50 transition">
                    <i class="fa-solid fa-cog mr-3 w-5"></i>
                    <span class="font-medium">Paramètres</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-gradient-to-r from-indigo-600 to-purple-700 shadow-lg border-b-4 border-indigo-800">
                <div class="px-8 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('storage/Logo/petit taille.png') }}" alt="Logo" class="h-10 w-auto">
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ env('APP_NAME', 'E-Client') }}</h1>
                            <p class="text-indigo-100 text-sm">Administration</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        <!-- Date & Time -->
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-medium text-white" id="current-date">{{ now()->format('d M Y') }}</p>
                            <p class="text-xs text-indigo-100" id="current-time">{{ now()->format('H:i') }}</p>
                        </div>

                        <!-- User Profile Dropdown -->
                        <div class="relative group">
                            <button class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-indigo-500/50 transition">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-indigo-600 font-bold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <div class="text-left hidden sm:block">
                                    <p class="text-sm font-semibold text-white">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-indigo-100">Admin</p>
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition first:rounded-t-lg">
                                    <i class="fa-solid fa-user w-4"></i> Mon profil
                                </a>
                                <a href="#" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition">
                                    <i class="fa-solid fa-bell w-4"></i> Notifications
                                </a>
                                <hr class="my-2">
                                <form action="/logout" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition rounded-b-lg">
                                        <i class="fa-solid fa-right-from-bracket w-4"></i> Déconnexion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <!-- Alerts -->
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3 animate-pulse">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg flex items-center gap-3">
                    <i class="fa-solid fa-exclamation-circle"></i>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span class="font-semibold">Erreurs de validation</span>
                    </div>
                    <ul class="ml-6 space-y-1">
                        @foreach($errors->all() as $error)
                        <li class="text-sm">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Update time every second
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        }
        setInterval(updateTime, 1000);
    </script>
</body>

</html>