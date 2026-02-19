@extends('clients.layouts.app')

@section('page-title','Mes paiements')

@section('content')
<div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Historique des paiements</h2>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Référence ou chèque" class="border-gray-300 rounded-lg text-sm">

        <input type="text" name="banque" value="{{ request('banque') }}" placeholder="Banque" class="border-gray-300 rounded-lg text-sm">

        <button type="submit" class="bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition py-2 text-sm font-medium">
            <i class="fa-solid fa-search mr-2"></i> Rechercher
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
                    <th class="p-4 border-b">Date</th>
                    <th class="p-4 border-b">Facture</th>
                    <th class="p-4 border-b">Référence</th>
                    <th class="p-4 border-b">Banque</th>
                    <th class="p-4 border-b text-right">Montant</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($paiements as $p)
                <tr class="hover:bg-gray-50 transition border-b">
                    <td class="p-4">{{ $p->date_paiement }}</td>
                    <td class="p-4">{{ $p->facture->numero_facture ?? '-' }}</td>
                    <td class="p-4">{{ $p->recu }}</td>
                    <td class="p-4">{{ $p->banque }}</td>
                    <td class="p-4 text-right font-bold text-green-600">+ {{ number_format($p->montant, 2) }} DA</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $paiements->links() }}
    </div>
</div>
@endsection