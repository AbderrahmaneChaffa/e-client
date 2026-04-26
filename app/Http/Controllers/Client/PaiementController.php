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
            $query->where('recu', 'like', '%' . $request->search . '%')
                  ->orWhere('numero_cheque', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('banque')) {
            $query->where('banque', $request->banque);
        }

        $paiements = $query->orderBy('date_paiement', 'desc')->paginate(100)->withQueryString();

        return view('clients.paiements.index', compact('paiements'));
    }
}
