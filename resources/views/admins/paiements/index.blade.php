@extends('admins.layouts.admin')

@section('content')
<div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">Historique des Paiements</h2>
        <div class="text-sm text-gray-500">
            Total : {{ number_format($paiements->total()) }} transactions
        </div>
    </div>

    <form method="GET" class="flex gap-4 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" 
               placeholder="Rechercher Reçu ou Chèque..." 
               class="flex-1 border-gray-300 rounded-lg text-sm">
        
        <select name="banque" class="border-gray-300 rounded-lg text-sm">
            <option value="">Toutes les banques</option>
            <option value="BEA">BEA</option>
            <option value="BNA">BNA</option>
            <option value="BADR">BADR</option>
            <option value="CPA">CPA</option>
        </select>

        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                    <th class="p-4 border-b">Date</th>
                    <th class="p-4 border-b">Client</th>
                    <th class="p-4 border-b">N° Facture</th>
                    <th class="p-4 border-b">Référence</th>
                    <th class="p-4 border-b">Banque</th>
                    <th class="p-4 border-b text-right">Montant Versé</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($paiements as $paiement)
                <tr class="hover:bg-gray-50 transition border-b">
                    <td class="p-4">{{ $paiement->date_paiement }}</td>
                    <td class="p-4 font-medium text-gray-700">
                        {{ $paiement->facture->client->name ?? 'N/A' }}
                    </td>
                    <td class="p-4">
                        <span class="text-blue-600 font-mono">{{ $paiement->facture->numero_facture }}</span>
                    </td>
                    <td class="p-4">
                        <div class="text-xs text-gray-500 italic">Recu: {{ $paiement->recu }}</div>
                        <div class="text-xs font-bold">Chq: {{ $paiement->numero_cheque }}</div>
                    </td>
                    <td class="p-4">
                        <span class="px-2 py-1 bg-slate-100 rounded text-xs">{{ $paiement->banque }}</span>
                    </td>
                    <td class="p-4 text-right font-bold text-green-600">
                        {{ number_format($paiement->montant, 2) }} DA
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-8 text-center text-gray-500 italic">Aucun paiement trouvé.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $paiements->links() }}
    </div>
</div>
@endsection