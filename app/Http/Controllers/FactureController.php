<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Facture;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Query de base avec relations pour éviter 20 000 requêtes SQL
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
        $factures = $query->orderBy('date_facture', 'desc')->paginate(15)->withQueryString();

        $clients = Client::select('id', 'name')->orderBy('name')->get();

        return view('admins.factures.index', compact('factures', 'clients'));
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
        //
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
