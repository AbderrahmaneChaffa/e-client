<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Facture;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NumberHelper;

class FactureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Query de base avec relations pour éviter 20 000 requêtes SQLhhh
        $query = Facture::with(['client', 'navire']);

        // 2. Filtre par numéro de facture
        if ($request->filled('numero')) {
            $query->where('numero_facture', 'like', '%' . $request->numero . '%');
        }

        // 3. Filtre par Client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // 4. Filtre par Statut (Logique de solde)
        if ($request->filled('statut')) {
            if ($request->statut === 'paye') {
                $query->where('reste_a_payer', '<=', 0);
            } elseif ($request->statut === 'impaye') {
                $query->where('reste_a_payer', '>', 0);
            }
        }

        // 5. Tri et Pagination ultra-rapide
        $factures = $query->orderBy('date_facture', 'desc')->paginate(100)->withQueryString();

        $clients = Client::select('id', 'name')->orderBy('name')->get();

        return view('admins.factures.index', compact('factures', 'clients'));
    }
    public function print(Facture $facture)
    {
        if ($facture->annuler) {
            return redirect()->back()->with('error', 'Impossible d\'imprimer une facture annulée.');
        }

        $facture->load(['client', 'navire', 'prestations', 'paiements']);

        if (!$facture->imprimer) {
            $facture->update([
                'imprimer' => true,
                'date_impression' => now(),
                'imprime_par' => Auth::id(),
            ]);
        }

        // --- NOUVEAU : Conversion du montant en lettres ---
        $montant = $facture->total_ttc;
        $dinars = floor($montant); // Partie entière
        $centimes = round(($montant - $dinars) * 100); // Partie décimale

        // Utilisation du formateur PHP natif en Français

        $montantEnLettres = NumberHelper::enLettres($facture->total_ttc);

        // --------------------------------------------------

        // On passe la nouvelle variable 'montantEnLettres' à la vue
        $pdf = Pdf::loadView('shared.prints.factures.pdf', compact('facture', 'montantEnLettres'));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Facture_' . $facture->numero . '.pdf');
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Facture $facture)
    {
        // On charge tout ce qui est lié à cette facture spécifique
        $facture->load(['client', 'navire', 'prestations', 'paiements']);

        return view('admins.factures.show', compact('facture'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Facture $facture)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Facture $facture)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Facture $facture)
    {
        //
    }
}
