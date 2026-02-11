<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EPO - Administration</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <aside class="w-64 bg-slate-800 text-white flex-shrink-0">
            <div class="p-6 text-center font-bold text-xl border-b border-slate-700">
                <i class="fa-solid fa-anchor mr-2"></i> E-Client
            </div>
            <nav class="mt-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center py-3 px-6 bg-blue-600 text-white">
                    <i class="fa-solid fa-chart-line mr-3"></i> Dashboard
                </a>
                <a href="{{route('admin.factures.index')}}" class="flex items-center py-3 px-6 text-slate-300 hover:bg-slate-700">
                    <i class="fa-solid fa-file-invoice mr-3"></i> Factures
                </a>
                <a href="{{route('admin.paiements.index')}}" class="flex items-center py-3 px-6 text-slate-300 hover:bg-slate-700">
                    <i class="fa-solid fa-file-invoice mr-3"></i> Paiements
                </a>
                <a href="#" class="flex items-center py-3 px-6 text-slate-300 hover:bg-slate-700">
                    <i class="fa-solid fa-users mr-3"></i> Clients
                </a>

                <a href="#" class="flex items-center py-3 px-6 text-slate-300 hover:bg-slate-700">
                    <i class="fa-solid fa-upload mr-3"></i> Import Excel
                </a>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow h-16 flex items-center justify-between px-8 text-sm">
                <span class="text-gray-600">Bienvenue, **Admin EPO**</span>
                <form action="/logout" method="POST">
                    @csrf
                    <button class="text-red-500 hover:text-red-700"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</button>
                </form>
            </header>

            <main class="flex-1 overflow-y-auto p-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>