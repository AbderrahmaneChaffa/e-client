<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $clientId = Auth::user()->client_id;
        $query = Paiement::with('facture')
            ->whereHas('facture', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recu', 'like', '%' . $search . '%')
                    ->orWhere('numero_cheque', 'like', '%' . $search . '%')
                    ->orWhereHas('facture', fn ($invoice) => $invoice->where('numero_facture', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('banque')) {
            $query->where('banque', $request->banque);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_paiement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_paiement', '<=', $request->date_to);
        }

        $allowedSorts = ['date_paiement', 'montant', 'recu', 'banque'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'date_paiement';
        $sortDir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);

        $paiements = $query->orderBy($sortBy, $sortDir)->paginate($perPage)->withQueryString();

        return view('clients.paiements.index', compact('paiements'));
    }
}
