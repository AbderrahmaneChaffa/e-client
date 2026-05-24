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
        $query = Facture::where('client_id', $clientId)->with('escale'); // Eager loading de l'escale pour éviter les N+1

        if ($request->filled('numero') || $request->filled('search')) {
            $search = $request->input('numero', $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('numero_facture', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'paye') {
                $query->where('reste_a_payer', '<=', 0);
            } elseif ($request->statut === 'impaye') {
                $query->where('reste_a_payer', '>', 0);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_facture', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_facture', '<=', $request->date_to);
        }

        if (! $request->filled('date_from') && ! $request->filled('date_to') && $request->filled('period')) {
            match ($request->period) {
                'today' => $query->whereDate('date_facture', today()),
                'week' => $query->whereBetween('date_facture', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereBetween('date_facture', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        $allowedSorts = ['date_facture', 'numero_facture', 'total_ttc', 'reste_a_payer'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'date_facture';
        $sortDir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);

        $factures = $query->orderBy($sortBy, $sortDir)->paginate($perPage)->withQueryString();

        return view('clients.factures.index', compact('factures'));
    }

    public function show(Facture $facture)
    {
        $this->authorizeFacture($facture);
        $facture->load(['escale.navire', 'prestations', 'paiements']);
        return view('clients.factures.show', compact('facture'));
    }

    protected function authorizeFacture(Facture $facture)
    {
        if ($facture->client_id !== Auth::user()->client_id) {
            abort(403);
        }
    }
}
