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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recu', 'like', '%' . $search . '%')
                    ->orWhere('numero_cheque', 'like', '%' . $search . '%')
                    ->orWhereHas('facture', fn ($invoice) => $invoice->where('numero_facture', 'like', '%' . $search . '%'))
                    ->orWhereHas('facture.client', fn ($client) => $client->where('name', 'like', '%' . $search . '%'));
            });
        }

        // Filtre par banque
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
