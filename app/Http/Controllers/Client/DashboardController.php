<?php
// app/Http/Controllers/Client/DashboardController.php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\{Facture, Paiement};
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $clientId = Auth::user()->client_id;

        // ── Stats financières (montant_paye n'existe pas) ─────────────────
        $stats = Facture::where('client_id', $clientId)
            ->active()
            ->selectRaw('
                COUNT(*)                          AS total_count,
                SUM(total_ttc)                    AS total_facture,
                SUM(total_ttc - reste_a_payer)    AS total_paye,
                SUM(reste_a_payer)                AS total_due,
                SUM(total_ht)                     AS total_ht,
                SUM(total_tva)                    AS total_tva
            ')
            ->first();

        $totalFacture = (float) ($stats->total_facture ?? 0);
        $totalPaye = (float) ($stats->total_paye ?? 0);
        $totalDue = (float) ($stats->total_due ?? 0);
        $totalCount = (int) ($stats->total_count ?? 0);

        // ── Taux de recouvrement ──────────────────────────────────────────
        $recoveryRate = $totalFacture > 0
            ? round(($totalPaye / $totalFacture) * 100, 1)
            : 0;

        // ── Compteurs statuts ─────────────────────────────────────────────
        $paidCount = Facture::where('client_id', $clientId)
            ->paid()->count();
        $unpaidCount = Facture::where('client_id', $clientId)
            ->unpaid()->count();
        $canceledCount = Facture::where('client_id', $clientId)
            ->canceled()->count();

        // ── Graphique mensuel — 12 mois ────────────────────────────────────
        $monthlyRaw = Paiement::selectRaw('
                YEAR(date_paiement)  AS annee,
                MONTH(date_paiement) AS mois,
                SUM(montant)         AS total,
                COUNT(*)             AS nb
            ')
            ->whereHas(
                'facture',
                fn($q) =>
                $q->where('client_id', $clientId)->active()
            )
            ->whereNotNull('date_paiement')
            ->where('date_paiement', '>=', now()->subMonths(12)->startOfMonth())
            ->groupByRaw('YEAR(date_paiement), MONTH(date_paiement)')
            ->orderByRaw('YEAR(date_paiement), MONTH(date_paiement)')
            ->get();

        $moisLabels = [];
        $moisAmounts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $annee = (int) $date->format('Y');
            $mois = (int) $date->format('n');

            $found = $monthlyRaw->first(
                fn($d) => (int) $d->annee === $annee && (int) $d->mois === $mois
            );

            $moisLabels[] = $date->locale('fr')->isoFormat('MMM YY');
            $moisAmounts[] = $found ? (float) $found->total : 0;
        }

        // ── Factures récentes ─────────────────────────────────────────────
        $recentInvoices = Facture::with('escale')
            ->where('client_id', $clientId)
            ->active()
            ->orderByDesc('date_facture')
            ->take(6)
            ->get();

        // ── Paiements récents ─────────────────────────────────────────────
        $recentPayments = Paiement::with('facture')
            ->whereHas(
                'facture',
                fn($q) =>
                $q->where('client_id', $clientId)->active()
            )
            ->whereNotNull('date_paiement')
            ->orderByDesc('date_paiement')
            ->take(6)
            ->get();

        // ── Factures impayées les plus anciennes (alertes) ────────────────
        $facturesEnRetard = Facture::where('client_id', $clientId)
            ->unpaid()
            ->orderBy('date_facture')
            ->take(5)
            ->get();

        return view('clients.dashboard', compact(
            'totalCount',
            'totalFacture',
            'totalPaye',
            'totalDue',
            'recoveryRate',
            'paidCount',
            'unpaidCount',
            'canceledCount',
            'moisLabels',
            'moisAmounts',
            'recentInvoices',
            'recentPayments',
            'facturesEnRetard',
            'stats'
        ));
    }
}
