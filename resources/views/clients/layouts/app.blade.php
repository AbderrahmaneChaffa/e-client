<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'E-Client') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
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
        <aside class="w-64 bg-white border-r border-gray-200 flex-shrink-0">
            <div class="p-6 text-center border-b">
                <h1 class="font-bold text-xl text-gray-800">E-Client</h1>
                <p class="text-xs text-gray-500">Portail Client</p>
            </div>
            <nav class="mt-6 space-y-1 px-4">
                <a href="{{ route('client.dashboard') }}" class="flex items-center py-2 px-3 rounded-lg {{ request()->routeIs('client.dashboard') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="fa-solid fa-chart-line mr-3 w-5"></i>
                    <span class="font-medium">Tableau de bord</span>
                </a>
                <a href="{{ route('client.factures.index') }}" class="flex items-center py-2 px-3 rounded-lg {{ request()->routeIs('client.factures.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="fa-solid fa-file-invoice mr-3 w-5"></i>
                    <span class="font-medium">Factures</span>
                </a>
                <a href="{{ route('client.paiements.index') }}" class="flex items-center py-2 px-3 rounded-lg {{ request()->routeIs('client.paiements.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="fa-solid fa-credit-card mr-3 w-5"></i>
                    <span class="font-medium">Paiements</span>
                </a>
                <a href="{{ route('profile.edit') }}" class="flex items-center py-2 px-3 rounded-lg {{ request()->routeIs('profile.*') ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100' }} transition">
                    <i class="fa-solid fa-user mr-3 w-5"></i>
                    <span class="font-medium">Mon profil</span>
                </a>
            </nav>
        </aside>

        <!-- Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-md border-b border-gray-200">
                <div class="px-8 py-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                    <form action="/logout" method="POST">
                        @csrf
                        <button class="text-red-600 hover:text-red-800 flex items-center gap-1">
                            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
                        </button>
                    </form>
                </div>
            </header>

            <!-- Main area -->
            <main class="flex-1 overflow-y-auto p-8">
                @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg flex items-center gap-3">
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
                        <span class="font-semibold">Erreurs</span>
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
</body>

</html>