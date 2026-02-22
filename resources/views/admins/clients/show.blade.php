@extends('admins.layouts.admin')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informations du client -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ $client->name }}</h2>
                    <p class="text-gray-500 text-sm mt-1">Code: <span class="font-semibold">{{ $client->code_client }}</span></p>
                    <p class="text-gray-500 text-sm mt-1">Email: <span class="font-semibold">{{ $client->email ?? '—'}}</span></p>
                    <p class="text-gray-500 text-sm mt-1">Adresse: <span class="font-semibold">{{ $client->adresse ?? '—' }}</span></p>


                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.clients.edit', $client) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                        <i class="fa-solid fa-edit mr-2"></i> Modifier
                    </a>
                    <form action="{{ route('admin.clients.destroy', $client) }}" method="POST" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition">
                            <i class="fa-solid fa-trash mr-2"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8 pb-8 border-b border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">NIS</label>
                    <p class="text-lg font-semibold text-gray-800">{{ $client->nis ?? '—' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">NIF</label>
                    <p class="text-lg font-semibold text-gray-800">{{ $client->nif ?? '—' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">RC</label>
                    <p class="text-lg font-semibold text-gray-800">{{ $client->rc ?? '—' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">AI</label>
                    <p class="text-lg font-semibold text-gray-800">{{ $client->ai ?? '—' }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Date de création</label>
                    <p class="text-lg font-semibold text-gray-800">{{ $client->created_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <!-- Section Factures -->
            <div>
                <h3 class="text-lg font-bold text-gray-800 mb-4">Factures du client</h3>

                @if($client->factures && count($client->factures) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold border-b">
                                <th class="p-3">N° Facture</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">D. Mises en ligne</th>
                                <th class="p-3">T. HT</th>
                                <th class="p-3">T. TVA</th>
                                <th class="p-3">T. TTC</th>
                                <th class="p-3">Payé</th>
                                <th class="p-3">Reste</th>
                                <th class="p-3 text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($client->factures->take(10) as $facture)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="p-3 font-semibold text-blue-600">
                                    <a href="{{ route('admin.factures.show', $facture) }}" class="hover:underline">
                                        {{ $facture->numero_facture }}
                                    </a>
                                </td>
                                <td class="p-3">{{ $facture->date_facture->format('d/m/Y') }}</td>
                                <td class="p-3">{{ $facture->created_at->format('d/m/Y') }}</td>
                                <td class="p-3 font-bold">{{ number_format($facture->total_ht, 2) }} DA</td>
                                <td class="p-3">{{ number_format($facture->total_tva, 2) }} DA</td>
                                <td class="p-3 font-bold">{{ number_format($facture->total_ttc, 2) }} DA</td>
                                <td class="p-3">{{ number_format($facture->montant_paye, 2) }} DA</td>
                                <td class="p-3 text-red-600 font-semibold">{{ number_format($facture->reste_a_payer, 2) }} DA</td>
                                <td class="p-3 text-center">
                                    @if($facture->reste_a_payer <= 0)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Payée</span>
                                        @else
                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">Impayée</span>
                                        @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(count($client->factures) > 10)
                <p class="text-sm text-gray-600 mt-4 text-center">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Seules les 10 dernières factures sont affichées
                </p>
                @endif
                @else
                <p class="text-gray-500 text-center py-8">
                    <i class="fa-solid fa-inbox text-2xl mb-2 opacity-50"></i>
                <p>Aucune facture pour ce client</p>
                </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar avec statistiques -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Statistiques -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Statistiques</h3>

            <div class="space-y-4">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <p class="text-gray-600 text-sm font-medium">Nombre de factures</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $client->factures()->count() }}</p>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <p class="text-gray-600 text-sm font-medium">Utilisateurs</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $client->users()->count() }}</p>
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <p class="text-gray-600 text-sm font-medium">Total facturé</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($client->factures()->sum('total_ttc'), 2) }} DA</p>
                </div>

                <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                    <p class="text-gray-600 text-sm font-medium">Reste à payer</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($client->factures()->sum('reste_a_payer'), 2) }} DA</p>
                </div>
            </div>
        </div>

        <!-- Utilisateurs du client -->
        @if($client->users && count($client->users) > 0)
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Utilisateurs</h3>

            <div class="space-y-3">
                @foreach($client->users as $user)
                <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="font-semibold text-gray-800 text-sm">{{ $user->name }}</p>
                    <p class="text-xs text-gray-600">{{ $user->email }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Actions rapides -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Actions</h3>

            <div class="space-y-2">
                <a href="{{ route('admin.clients.index') }}" class="w-full block text-center bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Retour
                </a>
                <a href="{{ route('admin.clients.edit', $client) }}" class="w-full block text-center bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                    <i class="fa-solid fa-edit mr-2"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>
@endsection