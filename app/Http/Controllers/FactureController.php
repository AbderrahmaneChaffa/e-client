<?php
// app/Http/Controllers/FactureController.php
namespace App\Http\Controllers;

use App\Models\{Client, Facture};
use App\Helpers\NumberHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $query = Facture::with(['client', 'escale.navire']);
        $hasVerificationColumns = Schema::hasColumn('factures', 'verification_status');

        // ── Filtres ──────────────────────────────────────────────────────────
        if ($request->filled('numero')) {
            $query->where('numero_facture', 'like', '%' . $request->numero . '%');
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('statut')) {
            match($request->statut) {
                'paye'    => $query->paid(),
                'impaye'  => $query->unpaid(),
                'annulee' => $query->canceled(),
                default   => null,
            };
        }

        if ($hasVerificationColumns && $request->filled('verification')) {
            match($request->verification) {
                'anomalies' => $query->whereIn('verification_status', ['warning', 'critical']),
                'critical' => $query->where('verification_status', 'critical'),
                'warning' => $query->where('verification_status', 'warning'),
                'ok' => $query->where('verification_status', 'ok'),
                default => null,
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
        if ($hasVerificationColumns && $request->filled('verification')) {
            match($request->verification) {
                'anomalies' => $statsQuery->whereIn('verification_status', ['warning', 'critical']),
                'critical' => $statsQuery->where('verification_status', 'critical'),
                'warning' => $statsQuery->where('verification_status', 'warning'),
                'ok' => $statsQuery->where('verification_status', 'ok'),
                default => null,
            };
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('date_facture', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('date_facture', '<=', $request->date_to);
        }

        $statusStats = (clone $statsQuery)
            ->selectRaw('
                COALESCE(SUM(CASE WHEN annuler = 0 THEN total_ht ELSE 0 END), 0) AS total_ht,
                COALESCE(SUM(CASE WHEN annuler = 0 THEN total_ttc ELSE 0 END), 0) AS total_ttc,
                COALESCE(SUM(CASE WHEN annuler = 0 THEN reste_a_payer ELSE 0 END), 0) AS reste_total,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer <= 0 THEN 1 ELSE 0 END) AS count_payees,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer > 0 THEN 1 ELSE 0 END) AS count_impayees,
                SUM(CASE WHEN annuler = 1 THEN 1 ELSE 0 END) AS count_annulees
            ')
            ->first();

        $stats = [
            'total_ht'       => (float) ($statusStats->total_ht ?? 0),
            'total_ttc'      => (float) ($statusStats->total_ttc ?? 0),
            'reste_total'    => (float) ($statusStats->reste_total ?? 0),
            'count_payees'   => (int) ($statusStats->count_payees ?? 0),
            'count_impayees' => (int) ($statusStats->count_impayees ?? 0),
            'count_annulees' => (int) ($statusStats->count_annulees ?? 0),
            'count_anomalies' => $hasVerificationColumns ? (clone $statsQuery)->whereIn('verification_status', ['warning', 'critical'])->count() : 0,
            'count_critical' => $hasVerificationColumns ? (clone $statsQuery)->where('verification_status', 'critical')->count() : 0,
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
