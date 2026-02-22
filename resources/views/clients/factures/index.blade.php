@extends('clients.layouts.app')

@section('page-title','Mes factures')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Mes factures</h2>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <input type="text" name="numero" value="{{ request('numero') }}" placeholder="N° facture..." class="border-gray-300 rounded-lg text-sm">

        <select name="statut" class="border-gray-300 rounded-lg text-sm">
            <option value="">Tous les statuts</option>
            <option value="paye" {{ request('statut') == 'paye' ? 'selected' : '' }}>Payées</option>
            <option value="impaye" {{ request('statut') == 'impaye' ? 'selected' : '' }}>Impayées</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition py-2 text-sm font-medium">
            <i class="fa-solid fa-search mr-2"></i> Rechercher
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="table w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
                    <th class="p-4 border-b">N° Facture</th>
                    <th class="p-4 border-b">Date</th>
                    <th class="p-4 border-b">D. Mises en ligne</th>
                    <th class="p-4 border-b">T. HT</th>
                    <th class="p-4 border-b">T. TVA</th>
                    <th class="p-4 border-b">T. TTC</th>
                    <th class="p-4 border-b">Reste</th>
                    <th class="p-4 border-b">Statut</th>
                    <th class="p-4 border-b text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($factures as $facture)
                <tr class="hover:bg-gray-50 transition border-b">
                    <td class="p-4 font-medium text-blue-600">{{ $facture->numero_facture }}</td>
                    <td class="p-4 border-b">{{ $facture->date_facture->format('d/m/Y') }}</td>
                    <td class="p-4 border-b">{{ $facture->created_at->format('d/m/Y H:i') }}</td>
                    <td class="p-4 border-b font-bold">{{ number_format($facture->total_ht, 2) }} DA</td>
                    <td class="p-4 border-b font-bold">{{ number_format($facture->total_tva, 2) }} DA</td>
                    <td class="p-4 border-b font-bold">{{ number_format($facture->total_ttc, 2) }} DA</td>
                    <td class="p-4 border-b text-red-500">{{ number_format($facture->reste_a_payer, 2) }} DA</td>
                    <td class="p-4 border-b">
                        @if($facture->reste_a_payer <= 0)
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">Payée</span>
                            @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-bold">Impayée</span>
                            @endif
                    </td>
                    <td class="p-4 border-b text-center">
                        <a href="{{ route('client.factures.show', $facture) }}" class="text-gray-400 hover:text-blue-600 mx-1">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $factures->links() }}
    </div>
</div>
@endsection