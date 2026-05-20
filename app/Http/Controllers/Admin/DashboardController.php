<?php
// app/Http/Controllers/Admin/DashboardController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\VerifyImportJob;
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
            return Facture::active()
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
        $invoiceStatusStats = Facture::query()
            ->selectRaw('
                COUNT(*) AS total_all,
                SUM(CASE WHEN annuler = 0 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer <= 0 THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer > 0 THEN 1 ELSE 0 END) AS unpaid_count,
                SUM(CASE WHEN annuler = 1 THEN 1 ELSE 0 END) AS canceled_count
            ')
            ->first();

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
        $totalInvoices = (int) ($invoiceStatusStats->active_count ?? 0);
        $paidInvoices = (int) ($invoiceStatusStats->paid_count ?? 0);
        $unpaidInvoices = (int) ($invoiceStatusStats->unpaid_count ?? 0);
        $canceledInvoices = (int) ($invoiceStatusStats->canceled_count ?? 0);

        // ── Graphique mensuel — 12 derniers mois ──────────────────────────
        [$paymentYearSql, $paymentMonthSql] = $this->yearMonthSql('date_paiement');
        $monthlyData = Paiement::selectRaw("
                {$paymentYearSql}  AS annee,
                {$paymentMonthSql} AS mois,
                SUM(montant)         AS total,
                COUNT(*)             AS nb_paiements
            ")
            ->whereNotNull('date_paiement')
            ->where('date_paiement', '>=', now()->subMonths(12)->startOfMonth())
            ->groupByRaw("{$paymentYearSql}, {$paymentMonthSql}")
            ->orderByRaw("{$paymentYearSql}, {$paymentMonthSql}")
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
        [$invoiceYearSql, $invoiceMonthSql] = $this->yearMonthSql('date_facture');
        $facturesParMois = Facture::selectRaw("
                {$invoiceYearSql}  AS annee,
                {$invoiceMonthSql} AS mois,
                COUNT(*)            AS nb,
                SUM(total_ttc)      AS montant
            ")
            ->active()
            ->where('date_facture', '>=', now()->subMonths(6)->startOfMonth())
            ->groupByRaw("{$invoiceYearSql}, {$invoiceMonthSql}")
            ->orderByRaw("{$invoiceYearSql}, {$invoiceMonthSql}")
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
        $debtorTotals = Facture::query()
            ->unpaid()
            ->select('client_id', DB::raw('SUM(reste_a_payer) AS total_due'))
            ->groupBy('client_id');
        $topDebiteurs = Client::query()
            ->joinSub($debtorTotals, 'debts', 'debts.client_id', '=', 'clients.id')
            ->select('clients.*')
            ->selectRaw('debts.total_due AS factures_sum_reste_a_payer')
            ->orderByDesc('debts.total_due')
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
            ->active()
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
        $verificationStatus = Cache::get(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY, [
            'status' => 'idle',
            'message' => null,
            'percentage' => null,
        ]);

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
            'dataHealth',
            'verificationStatus'
        ));
    }

    /**
     * @return array{0:string,1:string}
     */
    private function yearMonthSql(string $column): array
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return [
                "CAST(strftime('%Y', {$column}) AS INTEGER)",
                "CAST(strftime('%m', {$column}) AS INTEGER)",
            ];
        }

        return ["YEAR({$column})", "MONTH({$column})"];
    }
}
