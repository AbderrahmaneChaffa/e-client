<?php
// app/Http/Controllers/FactureController.php
namespace App\Http\Controllers;

use App\Models\{Client, Facture};
use App\Helpers\NumberHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $query = Facture::with(['client', 'escale.navire']);

        // ── Filtres ──────────────────────────────────────────────────────────
        if ($request->filled('numero')) {
            $query->where('numero_facture', 'like', '%' . $request->numero . '%');
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('statut')) {
            match($request->statut) {
                'paye'    => $query->where('annuler', 0)->where('reste_a_payer', '<=', 0),
                'impaye'  => $query->where('annuler', 0)->where('reste_a_payer', '>', 0),
                'annulee' => $query->where('annuler', 1),
                default   => null,
            };
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_facture', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_facture', '<=', $request->date_to);
        }

        // ── Tri ───────────────────────────────────────────────────────────────
        match($request->get('sort_by', 'date_desc')) {
            'date_asc'     => $query->orderBy('date_facture', 'asc'),
            'numero_asc'   => $query->orderBy('numero_facture', 'asc'),
            'numero_desc'  => $query->orderBy('numero_facture', 'desc'),
            'montant_desc' => $query->orderBy('total_ttc', 'desc'),
            'montant_asc'  => $query->orderBy('total_ttc', 'asc'),
            default        => $query->orderBy('date_facture', 'desc'),
        };

        $factures = $query->paginate(100)->withQueryString();

        // ── Stats globales (requêtes séparées sur TOUTES les factures filtrées)
        // !! Ne pas utiliser $factures->sum() : ne calcule que la page courante !!
        $statsQuery = Facture::query();

        if ($request->filled('client_id')) {
            $statsQuery->where('client_id', $request->client_id);
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('date_facture', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('date_facture', '<=', $request->date_to);
        }

        $stats = [
            'total_ht'       => (clone $statsQuery)->where('annuler', 0)->sum('total_ht'),
            'total_ttc'      => (clone $statsQuery)->where('annuler', 0)->sum('total_ttc'),
            'reste_total'    => (clone $statsQuery)->where('annuler', 0)->sum('reste_a_payer'),
            'count_payees'   => (clone $statsQuery)->where('annuler', 0)->where('reste_a_payer', '<=', 0)->count(),
            'count_impayees' => (clone $statsQuery)->where('annuler', 0)->where('reste_a_payer', '>', 0)->count(),
            'count_annulees' => (clone $statsQuery)->where('annuler', 1)->count(),
        ];

        $clients = Client::select('id', 'name')->orderBy('name')->get();

        return view('admins.factures.index', compact('factures', 'clients', 'stats'));
    }

    public function print(Facture $facture)
    {
        if ($facture->annuler) {
            return redirect()->back()->with('error', 'Impossible d\'imprimer une facture annulée.');
        }

        $facture->load(['client', 'escale.navire', 'prestations', 'paiements']);

        if (!$facture->imprimer) {
            $facture->update([
                'imprimer'         => true,
                'date_impression'  => now(),
                'imprime_par'      => Auth::id(),
            ]);
        }

        $montantEnLettres = NumberHelper::enLettres($facture->total_ttc);

        $pdf = Pdf::loadView('shared.prints.factures.pdf', compact('facture', 'montantEnLettres'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Facture_' . $facture->numero_facture . '.pdf');
    }

    public function show(Facture $facture)
    {
        $facture->load(['client', 'escale.navire', 'prestations', 'paiements']);
        return view('admins.factures.show', compact('facture'));
    }
}