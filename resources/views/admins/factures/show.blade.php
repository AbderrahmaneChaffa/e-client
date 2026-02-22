@extends('admins.layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-4">
        <a href="{{ route('admin.factures.index') }}" class="text-blue-600 hover:underline text-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i> Retour à la liste
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-50 flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Facture #{{ $facture->numero_facture }}</h1>
                <p class="text-gray-500">Émise le {{ $facture->date_facture->format('Y-m-d') }}</p>
            </div>
            <div class="text-right">
                <span class="px-4 py-2 rounded-full text-sm font-bold {{ $facture->reste_a_payer <= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $facture->reste_a_payer <= 0 ? 'TOTALEMENT PAYÉE' : 'SOLDE DÛ' }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-6 bg-gray-50/50">
            <div>
                <h3 class="text-xs uppercase font-bold text-gray-400 mb-2">Information Client</h3>
                <p class="font-bold text-gray-800">{{ $facture->client->name }}</p>
                <p class="text-sm text-gray-600">Code: {{ $facture->client->code_client }}</p>
                <p class="text-sm text-gray-600">NIS: {{ $facture->client->nis }}</p>
            </div>
            <div>
                <h3 class="text-xs uppercase font-bold text-gray-400 mb-2">Détails Navire</h3>
                <p class="font-bold text-gray-800">{{ $facture->navire->nom ?? 'N/A' }}</p>
                <p class="text-sm text-gray-600">Pavillon: {{ $facture->navire->pavillon ?? '-' }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm text-right">
                <h3 class="text-xs uppercase font-bold text-gray-400 mb-1">Reste à payer</h3>
                <p class="text-2xl font-black text-red-600">{{ number_format($facture->reste_a_payer, 2) }} DA</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 border-b font-bold bg-gray-50">Détails des Prestations</div>
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                    <th class="p-4">Désignation</th>
                    <th class="p-4 text-center">Qté</th>
                    <th class="p-4 text-right">P.U (HT)</th>
                    <th class="p-4 text-right">Total HT</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($facture->prestations as $item)
                <tr class="border-b last:border-0">
                    <td class="p-4 font-medium">{{ $item->libelle }} <span class="text-gray-400 text-xs ml-2">({{ $item->article }})</span></td>
                    <td class="p-4 text-center">{{ $item->quantite }}</td>
                    <td class="p-4 text-right">{{ number_format($item->prix_unitaire, 2) }}</td>
                    <td class="p-4 text-right font-bold">{{ number_format($item->total_ht, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold">
                <tr>
                    <td colspan="3" class="p-4 text-right">TOTAL HT</td>
                    <td class="p-4 text-right">{{ number_format($facture->total_ht, 2) }} DA</td>
                </tr>
                <tr>
                    <td colspan="3" class="p-4 text-right text-blue-600">TVA (19%)</td>
                    <td class="p-4 text-right text-blue-600">{{ number_format($facture->total_tva, 2) }} DA</td>
                </tr>
                <tr class="text-lg">
                    <td colspan="3" class="p-4 text-right">TOTAL TTC</td>
                    <td class="p-4 text-right">{{ number_format($facture->total_ttc, 2) }} DA</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b font-bold bg-green-50 text-green-800">Historique des Encaissements</div>
        @if($facture->paiements->count() > 0)
        <table class="w-full text-left">
            <thead>
                <tr class="text-xs font-bold text-gray-400 uppercase border-b">
                    <th class="p-4">Date</th>
                    <th class="p-4">Référence / Chèque</th>
                    <th class="p-4">Banque</th>
                    <th class="p-4 text-right text-green-700">Montant Versé</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($facture->paiements as $pay)
                <tr class="border-b last:border-0">
                    <td class="p-4">{{ $pay->date_paiement }}</td>
                    <td class="p-4">
                        <span class="font-bold">{{ $pay->recu }}</span><br>
                        <span class="text-xs text-gray-500">Chq: {{ $pay->numero_cheque }}</span>
                    </td>
                    <td class="p-4">{{ $pay->banque }}</td>
                    <td class="p-4 text-right font-bold text-green-600">+ {{ number_format($pay->montant, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-8 text-center text-gray-400 italic text-sm">
            Aucun paiement enregistré pour cette facture.
        </div>
        @endif
    </div>
</div>
@endsection