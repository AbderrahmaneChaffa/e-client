<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $clientId = Auth::user()->client_id;
        $query = Facture::where('client_id', $clientId)->with('navire');

        if ($request->filled('numero')) {
            $query->where('numero_facture', 'like', '%' . $request->numero . '%');
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'paye') {
                $query->where('reste_a_payer', '<=', 0);
            } elseif ($request->statut === 'impaye') {
                $query->where('reste_a_payer', '>', 0);
            }
        }

        $factures = $query->orderBy('date_facture', 'desc')->paginate(15)->withQueryString();

        return view('clients.factures.index', compact('factures'));
    }

    public function show(Facture $facture)
    {
        $this->authorizeFacture($facture);
        $facture->load(['navire', 'prestations', 'paiements']);
        return view('clients.factures.show', compact('facture'));
    }

    protected function authorizeFacture(Facture $facture)
    {
        if ($facture->client_id !== Auth::user()->client_id) {
            abort(403);
        }
    }
}
