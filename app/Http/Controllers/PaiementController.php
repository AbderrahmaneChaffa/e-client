<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // On charge la facture ET le client lié à cette facture en 2 requêtes seulement
        $query = Paiement::with(['facture.client']);

        // Filtre par référence de reçu ou chèque
        if ($request->filled('search')) {
            $query->where('recu', 'like', '%' . $request->search . '%')
                ->orWhere('numero_cheque', 'like', '%' . $request->search . '%');
        }

        // Filtre par banque
        if ($request->filled('banque')) {
            $query->where('banque', $request->banque);
        }

        $paiements = $query->orderBy('date_paiement', 'desc')->paginate(20)->withQueryString();

        return view('admins.paiements.index', compact('paiements'));
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
    public function show(Paiement $paiement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Paiement $paiement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Paiement $paiement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paiement $paiement)
    {
        //
    }
}
