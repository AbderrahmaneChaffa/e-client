@extends('admins.layouts.admin')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Gestion des Factures</h1>
                        <p class="text-gray-600 mt-1">{{ $factures->total() }} facture(s) au total</p>
                    </div>

                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-filter text-blue-600"></i> Filtres et Recherche
                    </h2>
                    @if(request()->hasAny(['numero', 'client_id', 'statut', 'verification', 'search']))
                        <a href="{{ route('admin.factures.index') }}"
                            class="text-sm text-gray-500 hover:text-gray-700 font-medium">
                            <i class="fa-solid fa-times mr-1"></i> Réinitialiser
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.factures.index') }}" class="space-y-4 md:space-y-0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <!-- Search by number -->
                        <div class="relative">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">N° Facture</label>
                            <input type="text" name="numero" value="{{ request('numero') }}" placeholder="Rechercher..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <!-- Client filter -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Client</label>
                            <select name="client_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="">Tous les clients</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status filter -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Statut</label>
                            <select name="statut"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="">Tous les statuts</option>
                                <option value="paye" {{ request('statut') == 'paye' ? 'selected' : '' }}>Payées</option>
                                <option value="impaye" {{ request('statut') == 'impaye' ? 'selected' : '' }}>Impayées</option>
                                <option value="annulee" {{ request('statut') == 'annulee' ? 'selected' : '' }}>Annulées
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Verification</label>
                            <select name="verification"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="">Toutes</option>
                                <option value="anomalies" {{ request('verification') == 'anomalies' ? 'selected' : '' }}>Avec anomalies</option>
                                <option value="critical" {{ request('verification') == 'critical' ? 'selected' : '' }}>Erreurs</option>
                                <option value="warning" {{ request('verification') == 'warning' ? 'selected' : '' }}>Avertissements</option>
                                <option value="ok" {{ request('verification') == 'ok' ? 'selected' : '' }}>OK</option>
                            </select>
                        </div>

                        <!-- Date range (optional) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Date de</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <!-- Date to (optional) -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Date à</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>
                    </div>

                    <!-- Sort and action buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">Trier par</label>
                            <select name="sort_by"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="date_desc" {{ request('sort_by') == 'date_desc' ? 'selected' : '' }}>Date (Plus
                                    récent)</option>
                                <option value="date_asc" {{ request('sort_by') == 'date_asc' ? 'selected' : '' }}>Date (Plus
                                    ancien)</option>
                                <option value="numero_asc" {{ request('sort_by') == 'numero_asc' ? 'selected' : '' }}>N°
                                    Facture (A-Z)</option>
                                <option value="numero_desc" {{ request('sort_by') == 'numero_desc' ? 'selected' : '' }}>N°
                                    Facture (Z-A)</option>
                                <option value="montant_desc" {{ request('sort_by') == 'montant_desc' ? 'selected' : '' }}>
                                    Montant (Plus élevé)</option>
                                <option value="montant_asc" {{ request('sort_by') == 'montant_asc' ? 'selected' : '' }}>
                                    Montant (Plus bas)</option>
                            </select>
                        </div>
                        <div class="flex gap-2 sm:pt-6">
                            <button type="submit"
                                class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-search"></i> <span class="hidden sm:inline">Appliquer</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <!-- Stats Bar -->
                <!-- <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 md:p-6 border-b border-gray-200 bg-gray-50">
                    <div class="text-center">
                        <p class="text-gray-600 text-xs md:text-sm font-medium">Total HT</p>
                        <p class="text-lg md:text-xl font-bold text-gray-900">
                            {{ number_format($factures->sum('total_ht'), 2) }} DA
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs md:text-sm font-medium">Total TTC</p>
                        <p class="text-lg md:text-xl font-bold text-gray-900">
                            {{ number_format($factures->sum('total_ttc'), 2) }} DA
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs md:text-sm font-medium">Payées</p>
                        <p class="text-lg md:text-xl font-bold text-green-600">
                            {{ $factures->where('reste_a_payer', '<=', 0)->count() }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs md:text-sm font-medium">Impayées</p>
                        <p class="text-lg md:text-xl font-bold text-red-600">
                            {{ $factures->where('reste_a_payer', '>', 0)->count() }}
                        </p>
                    </div>
                </div> -->
                {{-- Stats Bar — utilise $stats du contrôleur, pas $factures->sum() --}}
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 p-4 md:p-6 border-b border-gray-200 bg-gray-50">
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Total HT</p>
                        <p class="text-base md:text-lg font-bold text-gray-900">
                            {{ number_format($stats['total_ht'], 2, ',', ' ') }} DA
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Total TTC</p>
                        <p class="text-base md:text-lg font-bold text-gray-900">
                            {{ number_format($stats['total_ttc'], 2, ',', ' ') }} DA
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Reste à payer</p>
                        <p class="text-base md:text-lg font-bold text-orange-600">
                            {{ number_format($stats['reste_total'], 2, ',', ' ') }} DA
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Payées</p>
                        <p class="text-base md:text-lg font-bold text-green-600">
                            {{ number_format($stats['count_payees'], 0, ',', ' ') }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Impayées</p>
                        <p class="text-base md:text-lg font-bold text-red-600">
                            {{ number_format($stats['count_impayees'], 0, ',', ' ') }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600 text-xs font-medium">Annulées</p>
                        <p class="text-base md:text-lg font-bold text-gray-400">
                            {{ number_format($stats['count_annulees'], 0, ',', ' ') }}
                        </p>
                    </div>
                </div>
                <!-- Responsive Table -->
                <div class="overflow-x-auto">
                    @if($factures->count())
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 md:px-6 text-left font-semibold text-gray-900 text-xs md:text-sm">#
                                    </th>
                                    <th class="px-4 py-3 md:px-6 text-left font-semibold text-gray-900 text-xs md:text-sm">N°
                                        Facture</th>
                                    <th
                                        class="px-4 py-3 md:px-6 text-left font-semibold text-gray-900 text-xs md:text-sm hidden sm:table-cell">
                                        Date</th>
                                    <th class="px-4 py-3 md:px-6 text-left font-semibold text-gray-900 text-xs md:text-sm">
                                        Client</th>
                                    <th class="px-4 py-3 md:px-6 text-right font-semibold text-gray-900 text-xs md:text-sm">
                                        Montant</th>
                                    <th
                                        class="px-4 py-3 md:px-6 text-left font-semibold text-gray-900 text-xs md:text-sm hidden md:table-cell">
                                        Statut</th>
                                    <th class="px-4 py-3 md:px-6 text-center font-semibold text-gray-900 text-xs md:text-sm hidden lg:table-cell">
                                        Verification</th>
                                    <th class="px-4 py-3 md:px-6 text-center font-semibold text-gray-900 text-xs md:text-sm">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($factures as $facture)
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-4 py-3 md:px-6 text-gray-600 font-medium text-xs md:text-sm">
                                            {{ ($factures->currentPage() - 1) * $factures->perPage() + $loop->iteration }}
                                        </td>
                                        <td class="px-4 py-3 md:px-6">
                                            <a href="{{ route('admin.factures.show', $facture) }}"
                                                class="font-semibold text-blue-600 hover:text-blue-800 transition text-xs md:text-sm">
                                                {{ $facture->numero_facture }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 md:px-6 text-gray-700 text-xs md:text-sm hidden sm:table-cell">
                                            {{ $facture->date_facture?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 md:px-6">
                                            <div class="text-xs md:text-sm">
                                                <p class="font-medium text-gray-900">{{ $facture->client?->name ?? 'Client introuvable' }}</p>
                                                <p class="text-gray-500 text-xs hidden md:block">
                                                    {{ $facture->created_at->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 md:px-6 text-right">
                                            <div class="text-xs md:text-sm">
                                                <p class="font-bold text-gray-900">{{ number_format($facture->total_ttc, 2) }} DA
                                                </p>
                                                <p class="text-red-600 font-semibold text-xs">
                                                    @if($facture->reste_a_payer > 0)
                                                        Dû: {{ number_format($facture->reste_a_payer, 2) }} DA
                                                    @else
                                                        Payée
                                                    @endif
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 md:px-6 hidden md:table-cell">
                                            @if($facture->annuler)
                                                <span
                                                    class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                    <i class="fa-solid fa-ban"></i> Annulée
                                                </span>
                                            @elseif($facture->reste_a_payer <= 0)
                                                <span
                                                    class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                    <i class="fa-solid fa-check-circle"></i> Payée
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold">
                                                    <i class="fa-solid fa-clock"></i> Impayée
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 md:px-6 text-center hidden lg:table-cell">
                                            @include('admins.factures.partials.verification-badge', ['facture' => $facture])
                                        </td>
                                        <td class="px-4 py-3 md:px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <!-- View -->
                                                <a href="{{ route('admin.factures.show', $facture) }}"
                                                    class="text-gray-500 hover:text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition-all duration-200"
                                                    title="Voir">
                                                    <i class="fa-solid fa-eye text-sm md:text-base"></i>
                                                </a>

                                                <!-- Print -->
                                                @if(!$facture->annuler)
                                                    <a href="{{ route('admin.factures.print', $facture->id) }}" target="_blank"
                                                        class="text-gray-500 hover:text-green-600 hover:bg-green-50 p-2 rounded-lg transition-all duration-200"
                                                        title="Imprimer">
                                                        <i class="fa-solid fa-print text-sm md:text-base"></i>
                                                    </a>
                                                @endif

                                                <!-- Download PDF -->
                                                <!-- @if(!$facture->annuler)

                                                <a href="#"
                                                    class="text-gray-500 hover:text-red-600 hover:bg-red-50 p-2 rounded-lg transition-all duration-200"
                                                    title="Télécharger">
                                                    <i class="fa-solid fa-download text-sm md:text-base"></i>
                                                </a>
                                                @endif -->


                                                <!-- Dropdown Menu -->
                                                <div class="relative group">
                                                    <button type="button"
                                                        class="text-gray-500 hover:text-gray-700 hover:bg-gray-100 p-2 rounded-lg transition-all duration-200">
                                                        <i class="fa-solid fa-ellipsis-v text-sm md:text-base"></i>
                                                    </button>
                                                    <div
                                                        class="hidden group-hover:block absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                        @if(!$facture->annuler)

                                                            <a href="#"
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                                                <i class="fa-solid fa-envelope mr-2"></i> Envoyer par email
                                                            </a>
                                                        @endif

                                                        <a href="#"
                                                            class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition">
                                                            <i class="fa-solid fa-history mr-2"></i> Historique
                                                        </a>
                                                        @if(!$facture->annuler)

                                                            <form action="#" method="POST" class="block"
                                                                onsubmit="return confirm('Êtes-vous sûr? Cette action est irréversible.')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition">
                                                                    <i class="fa-solid fa-trash mr-2"></i> Annuler la facture
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Mobile row details (shown below main row on mobile) -->
                                    <tr class="bg-gray-50 md:hidden">
                                        <td colspan="7" class="px-4 py-3">
                                            <div class="space-y-2 text-xs">
                                                @if($facture->annuler)
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Statut:</span>
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-red-50 text-red-700 px-2 py-1 rounded-full font-semibold">
                                                            <i class="fa-solid fa-ban"></i> Annulée
                                                        </span>
                                                    </div>
                                                @elseif($facture->reste_a_payer <= 0)
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Statut:</span>
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2 py-1 rounded-full font-semibold">
                                                            <i class="fa-solid fa-check-circle"></i> Payée
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Statut:</span>
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-orange-50 text-orange-700 px-2 py-1 rounded-full font-semibold">
                                                            <i class="fa-solid fa-clock"></i> Impayée
                                                        </span>
                                                    </div>
                                                @endif
                                                <div class="flex justify-between">
                                                    <span class="text-gray-600">Créée:</span>
                                                    <span class="font-medium">{{ $facture->created_at->format('d/m/Y H:i') }}</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-8 md:p-12 text-center">
                            <i class="fa-solid fa-inbox text-5xl text-gray-300 mb-4 block"></i>
                            <h3 class="text-lg font-semibold text-gray-600 mb-2">Aucune facture trouvée</h3>
                            <p class="text-gray-500 mb-6">Modifiez vos filtres ou créez une nouvelle facture</p>
                            <a href="{{ route('admin.factures.index') }}"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg transition-colors">
                                <i class="fa-solid fa-arrow-left"></i> Voir toutes les factures
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Pagination -->
                @if($factures->count())
                    <div class="px-4 py-6 md:px-6 border-t border-gray-200 bg-gray-50">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="text-sm text-gray-600">
                                Affichage de <span
                                    class="font-semibold text-gray-900">{{ ($factures->currentPage() - 1) * $factures->perPage() + 1 }}</span>
                                à <span
                                    class="font-semibold text-gray-900">{{ min($factures->currentPage() * $factures->perPage(), $factures->total()) }}</span>
                                sur <span class="font-semibold text-gray-900">{{ $factures->total() }}</span> résultats
                            </div>
                            <div class="flex justify-center md:justify-end">
                                {{ $factures->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Custom styles for better mobile responsiveness -->
    <style>
        /* Pagination buttons styling */
        .pagination {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination a,
        .pagination span {
            @apply px-3 py-1 text-sm border border-gray-300 rounded-lg transition-colors;
        }

        .pagination a:hover {
            @apply bg-blue-50 border-blue-300 text-blue-600;
        }

        .pagination .active span {
            @apply bg-blue-600 text-white border-blue-600;
        }

        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
@endsection
