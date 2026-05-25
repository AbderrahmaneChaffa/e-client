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
        $hasImportDiffColumns = Schema::hasColumn('factures', 'import_diff_status');

        // ── Filtres ──────────────────────────────────────────────────────────
        if ($request->filled('numero') || $request->filled('search')) {
            $search = $request->input('numero', $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('numero_facture', 'like', '%' . $search . '%')
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code_client', 'like', '%' . $search . '%'));
            });
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

        if ($hasImportDiffColumns && $request->filled('ecart')) {
            match($request->ecart) {
                'any' => $query->whereNotNull('import_diff_status'),
                'new' => $query->where('import_diff_status', 'new'),
                'modified' => $query->where('import_diff_status', 'modified'),
                'missing' => $query->where('import_diff_status', 'missing'),
                'inconsistent' => $query->where('import_diff_status', 'inconsistent'),
                default => null,
            };
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

        if ($request->filled('amount_min')) {
            $query->where('total_ttc', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('total_ttc', '<=', $request->amount_max);
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

        $perPage = min((int) $request->input('per_page', 25), 100);
        $factures = $query->paginate($perPage)->withQueryString();

        // ── Stats globales (requêtes séparées sur TOUTES les factures filtrées)
        // !! Ne pas utiliser $factures->sum() : ne calcule que la page courante !!
        $statsQuery = Facture::query();

        if ($request->filled('numero') || $request->filled('search')) {
            $search = $request->input('numero', $request->input('search'));
            $statsQuery->where(function ($q) use ($search) {
                $q->where('numero_facture', 'like', '%' . $search . '%')
                    ->orWhereHas('client', fn ($client) => $client->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code_client', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('client_id')) {
            $statsQuery->where('client_id', $request->client_id);
        }
        if ($request->filled('statut')) {
            match($request->statut) {
                'paye' => $statsQuery->paid(),
                'impaye' => $statsQuery->unpaid(),
                'annulee' => $statsQuery->canceled(),
                default => null,
            };
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
        if ($hasImportDiffColumns && $request->filled('ecart')) {
            match($request->ecart) {
                'any' => $statsQuery->whereNotNull('import_diff_status'),
                'new' => $statsQuery->where('import_diff_status', 'new'),
                'modified' => $statsQuery->where('import_diff_status', 'modified'),
                'missing' => $statsQuery->where('import_diff_status', 'missing'),
                'inconsistent' => $statsQuery->where('import_diff_status', 'inconsistent'),
                default => null,
            };
        }
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('date_facture', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('date_facture', '<=', $request->date_to);
        }
        if (! $request->filled('date_from') && ! $request->filled('date_to') && $request->filled('period')) {
            match ($request->period) {
                'today' => $statsQuery->whereDate('date_facture', today()),
                'week' => $statsQuery->whereBetween('date_facture', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $statsQuery->whereBetween('date_facture', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }
        if ($request->filled('amount_min')) {
            $statsQuery->where('total_ttc', '>=', $request->amount_min);
        }
        if ($request->filled('amount_max')) {
            $statsQuery->where('total_ttc', '<=', $request->amount_max);
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
            'count_ecarts' => $hasImportDiffColumns ? (clone $statsQuery)->whereNotNull('import_diff_status')->count() : 0,
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
        $importDiffs = Schema::hasTable('import_diffs')
            ? $facture->importDiffs()->with('importBatch')->latest('id')->limit(20)->get()
            : collect();

        return view('admins.factures.show', compact('facture', 'importDiffs'));
    }
}
