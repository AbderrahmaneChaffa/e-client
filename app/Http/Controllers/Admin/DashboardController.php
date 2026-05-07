<?php
// app/Http/Controllers/Admin/DashboardController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Client, Facture, ImportBatch, Paiement, User};
use App\Services\ImportVerificationService;
use Illuminate\Support\Facades\{Cache, DB};

class DashboardController extends Controller
{
    public function index()
    {
        // ── Stats financières principales ────────────────────────────────
        // montant_paye n'existe pas → total_ttc - reste_a_payer
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return Facture::where('annuler', 0)
                ->selectRaw('
                    COUNT(*)                          AS total_count,
                    SUM(total_ttc)                    AS total_facture,
                    SUM(total_ttc - reste_a_payer)    AS total_encaisse,
                    SUM(reste_a_payer)                AS total_impayes,
                    SUM(total_ht)                     AS total_ht,
                    SUM(total_tva)                    AS total_tva
                ')
                ->first();
        });

        // ── Taux de recouvrement ──────────────────────────────────────────
        $totalFacture = (float) ($stats->total_facture ?? 0);
        $totalEncaisse = (float) ($stats->total_encaisse ?? 0);
        $recoveryRate = $totalFacture > 0
            ? round(($totalEncaisse / $totalFacture) * 100, 1)
            : 0;

        // ── Compteurs utilisateurs ────────────────────────────────────────
        $totalClients = Client::count();
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalClientUsers = User::where('role', 'client')->count();

        // ── Compteurs factures ────────────────────────────────────────────
        $totalInvoices = Facture::where('annuler', 0)->count();
        $paidInvoices = Facture::where('annuler', 0)->where('reste_a_payer', '<=', 0)->count();
        $unpaidInvoices = Facture::where('annuler', 0)->where('reste_a_payer', '>', 0)->count();
        $canceledInvoices = Facture::where('annuler', 1)->count();

        // ── Graphique mensuel — 12 derniers mois ──────────────────────────
        $monthlyData = Paiement::selectRaw('
                YEAR(date_paiement)  AS annee,
                MONTH(date_paiement) AS mois,
                SUM(montant)         AS total,
                COUNT(*)             AS nb_paiements
            ')
            ->whereNotNull('date_paiement')
            ->where('date_paiement', '>=', now()->subMonths(12)->startOfMonth())
            ->groupByRaw('YEAR(date_paiement), MONTH(date_paiement)')
            ->orderByRaw('YEAR(date_paiement), MONTH(date_paiement)')
            ->get();

        // Remplir les mois manquants avec 0
        $moisLabels = [];
        $moisAmounts = [];
        $moisCounts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $annee = (int) $date->format('Y');
            $mois = (int) $date->format('n');

            $found = $monthlyData->first(
                fn($d) => (int) $d->annee === $annee && (int) $d->mois === $mois
            );

            $moisLabels[] = $date->locale('fr')->isoFormat('MMM YY');
            $moisAmounts[] = $found ? (float) $found->total : 0;
            $moisCounts[] = $found ? (int) $found->nb_paiements : 0;
        }

        // ── Graphique factures par mois (6 derniers mois) ─────────────────
        $facturesParMois = Facture::selectRaw('
                YEAR(date_facture)  AS annee,
                MONTH(date_facture) AS mois,
                COUNT(*)            AS nb,
                SUM(total_ttc)      AS montant
            ')
            ->where('annuler', 0)
            ->where('date_facture', '>=', now()->subMonths(6)->startOfMonth())
            ->groupByRaw('YEAR(date_facture), MONTH(date_facture)')
            ->orderByRaw('YEAR(date_facture), MONTH(date_facture)')
            ->get();

        $facturesLabels = [];
        $facturesMontants = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $annee = (int) $date->format('Y');
            $mois = (int) $date->format('n');

            $found = $facturesParMois->first(
                fn($d) => (int) $d->annee === $annee && (int) $d->mois === $mois
            );

            $facturesLabels[] = $date->locale('fr')->isoFormat('MMM YY');
            $facturesMontants[] = $found ? (float) $found->montant : 0;
        }

        // ── Top 5 débiteurs ───────────────────────────────────────────────
        $topDebiteurs = Client::withSum(
            ['factures' => fn($q) => $q->where('annuler', 0)->where('reste_a_payer', '>', 0)],
            'reste_a_payer'
        )
            ->having('factures_sum_reste_a_payer', '>', 0)
            ->orderByDesc('factures_sum_reste_a_payer')
            ->take(5)
            ->get();

        // ── Top 5 meilleurs payeurs ───────────────────────────────────────
        $topPayeurs = Client::whereIn('id', function ($sub) {
            $sub->select('client_id')
                ->from('factures')
                ->where('annuler', 0);
        })
            ->get()
            ->map(function ($client) {
                $client->montant_paye_total = DB::table('factures')
                    ->where('client_id', $client->id)
                    ->where('annuler', 0)
                    ->sum(DB::raw('total_ttc - reste_a_payer'));
                return $client;
            })
            ->sortByDesc('montant_paye_total')
            ->take(5)
            ->values();

        // ── Derniers imports ──────────────────────────────────────────────
        $recentImports = ImportBatch::with('creator')
            ->latest()
            ->take(5)
            ->get();

        // ── Factures récentes ─────────────────────────────────────────────
        $recentInvoices = Facture::with('client')
            ->where('annuler', 0)
            ->orderByDesc('date_facture')
            ->take(8)
            ->get();

        // ── Paiements récents ─────────────────────────────────────────────
        $recentPayments = Paiement::with('facture.client')
            ->whereNotNull('date_paiement')
            ->orderByDesc('date_paiement')
            ->take(8)
            ->get();

        // ── Activité cette semaine ────────────────────────────────────────
        $weekStats = [
            'factures' => Facture::where('created_at', '>=', now()->startOfWeek())->count(),
            'paiements' => Paiement::where('created_at', '>=', now()->startOfWeek())->count(),
            'clients' => Client::where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        $dataHealth = app(ImportVerificationService::class)->latestHealthSummary();

        return view('admins.dashboard', compact(
            'stats',
            'totalClients',
            'totalUsers',
            'totalAdmins',
            'totalClientUsers',
            'recoveryRate',
            'totalInvoices',
            'paidInvoices',
            'unpaidInvoices',
            'canceledInvoices',
            'moisLabels',
            'moisAmounts',
            'moisCounts',
            'facturesLabels',
            'facturesMontants',
            'topDebiteurs',
            'topPayeurs',
            'recentImports',
            'recentInvoices',
            'recentPayments',
            'weekStats',
            'dataHealth'
        ));
    }
}
