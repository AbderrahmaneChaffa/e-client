<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tableau de bord client.
     */
    public function index(): View
    {
        $clientId = $this->clientId();

        $stats = Facture::query()
            ->where('client_id', $clientId)
            ->active()
            ->selectRaw('
                COUNT(*) AS total_count,
                COALESCE(SUM(total_ttc), 0) AS total_facture,
                COALESCE(SUM(total_ttc - reste_a_payer), 0) AS total_paye,
                COALESCE(SUM(reste_a_payer), 0) AS total_due,
                COALESCE(SUM(total_ht), 0) AS total_ht,
                COALESCE(SUM(total_tva), 0) AS total_tva
            ')
            ->first();

        $totalFacture = (float) ($stats->total_facture ?? 0);
        $totalPaye = (float) ($stats->total_paye ?? 0);
        $totalDue = (float) ($stats->total_due ?? 0);
        $totalCount = (int) ($stats->total_count ?? 0);

        $recoveryRate = $totalFacture > 0
            ? round(($totalPaye / $totalFacture) * 100, 1)
            : 0.0;

        $statusStats = Facture::query()
            ->where('client_id', $clientId)
            ->selectRaw('
                SUM(CASE WHEN annuler = 0 AND reste_a_payer <= 0 THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN annuler = 0 AND reste_a_payer > 0 THEN 1 ELSE 0 END) AS unpaid_count,
                SUM(CASE WHEN annuler = 1 THEN 1 ELSE 0 END) AS canceled_count
            ')
            ->first();

        $paidCount = (int) ($statusStats->paid_count ?? 0);
        $unpaidCount = (int) ($statusStats->unpaid_count ?? 0);
        $canceledCount = (int) ($statusStats->canceled_count ?? 0);

        $recoveryTone = $this->recoveryTone($recoveryRate);
        $recoveryRoute = route('client.paiements.index', [
            'date_from' => now()->subMonths(11)->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ]);

        $monthlySeries = $this->monthlySeries($clientId);
        $agingBalances = $this->agingBalances($clientId);

        $nextDueInvoice = Facture::query()
            ->where('client_id', $clientId)
            ->active()
            ->unpaid()
            ->whereNotNull('date_echeance')
            ->with('escale.navire')
            ->orderBy('date_echeance')
            ->orderByDesc('id')
            ->first();

        return view('clients.dashboard', [
            'totalCount' => $totalCount,
            'totalFacture' => $totalFacture,
            'totalPaye' => $totalPaye,
            'totalDue' => $totalDue,
            'recoveryRate' => $recoveryRate,
            'recoveryTone' => $recoveryTone,
            'recoveryRoute' => $recoveryRoute,
            'paidCount' => $paidCount,
            'unpaidCount' => $unpaidCount,
            'canceledCount' => $canceledCount,
            'distributionCards' => [
                [
                    'label' => 'Payées',
                    'value' => $paidCount,
                    'route' => route('client.factures.index', ['statut' => 'payee']),
                    'color' => 'success',
                ],
                [
                    'label' => 'Impayées',
                    'value' => $unpaidCount,
                    'route' => route('client.factures.index', ['statut' => 'impayee']),
                    'color' => 'danger',
                ],
                [
                    'label' => 'Annulées',
                    'value' => $canceledCount,
                    'route' => route('client.factures.index', ['statut' => 'annulee']),
                    'color' => 'slate',
                ],
            ],
            'monthlyLabels' => $monthlySeries['labels'],
            'monthlyPaidValues' => $monthlySeries['paid'],
            'monthlyRemainingValues' => $monthlySeries['remaining'],
            'monthlyPaidRoutes' => $monthlySeries['paid_routes'],
            'monthlyRemainingRoutes' => $monthlySeries['remaining_routes'],
            'nextDueInvoice' => $nextDueInvoice,
            'agingBalances' => $agingBalances,
            'stats' => $stats,
        ]);
    }

    /**
     * @return array{labels: array<int, string>, paid: array<int, float>, remaining: array<int, float>, paid_routes: array<int, string>, remaining_routes: array<int, string>}
     */
    private function monthlySeries(int $clientId): array
    {
        $driver = DB::connection()->getDriverName();
        $yearExpression = $driver === 'sqlite'
            ? "CAST(strftime('%Y', date_facture) AS INTEGER)"
            : 'YEAR(date_facture)';
        $monthExpression = $driver === 'sqlite'
            ? "CAST(strftime('%m', date_facture) AS INTEGER)"
            : 'MONTH(date_facture)';

        $raw = Facture::query()
            ->where('client_id', $clientId)
            ->active()
            ->whereBetween('date_facture', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('
                '.$yearExpression.' AS annee,
                '.$monthExpression.' AS mois,
                COALESCE(SUM(total_ttc), 0) AS total_ttc,
                COALESCE(SUM(reste_a_payer), 0) AS reste_total
            ')
            ->groupByRaw($yearExpression.', '.$monthExpression)
            ->orderByRaw($yearExpression.', '.$monthExpression)
            ->get();

        $labels = [];
        $paid = [];
        $remaining = [];
        $paidRoutes = [];
        $remainingRoutes = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $annee = (int) $date->format('Y');
            $mois = (int) $date->format('n');
            $item = $raw->first(fn ($row) => (int) $row->annee === $annee && (int) $row->mois === $mois);
            $paidValue = $item ? max(0, (float) $item->total_ttc - (float) $item->reste_total) : 0.0;
            $remainingValue = $item ? (float) $item->reste_total : 0.0;

            $labels[] = $date->locale('fr')->isoFormat('MMM YY');
            $paid[] = round($paidValue, 2);
            $remaining[] = round($remainingValue, 2);
            $paidRoutes[] = route('client.factures.index', [
                'annee' => $annee,
                'mois' => $mois,
                'statut' => 'payee',
            ]);
            $remainingRoutes[] = route('client.factures.index', [
                'annee' => $annee,
                'mois' => $mois,
                'statut' => 'impayee',
            ]);
        }

        return [
            'labels' => $labels,
            'paid' => $paid,
            'remaining' => $remaining,
            'paid_routes' => $paidRoutes,
            'remaining_routes' => $remainingRoutes,
        ];
    }

    /**
     * @return array<int,array{label:string,amount:float,count:int,percentage:float,color:string}>
     */
    private function agingBalances(int $clientId): array
    {
        $invoices = Facture::query()
            ->where('client_id', $clientId)
            ->active()
            ->unpaid()
            ->whereNotNull('date_echeance')
            ->get(['reste_a_payer', 'date_echeance']);

        $buckets = [
            'moins_30' => ['label' => '< 30 j', 'amount' => 0.0, 'count' => 0, 'color' => 'success'],
            'entre_30_60' => ['label' => '30 - 60 j', 'amount' => 0.0, 'count' => 0, 'color' => 'warning'],
            'plus_60' => ['label' => '> 60 j', 'amount' => 0.0, 'count' => 0, 'color' => 'danger'],
        ];

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->date_echeance);
            $daysLate = max(0, $dueDate->diffInDays(today(), false));

            if ($daysLate < 30) {
                $bucket = 'moins_30';
            } elseif ($daysLate <= 60) {
                $bucket = 'entre_30_60';
            } else {
                $bucket = 'plus_60';
            }

            $buckets[$bucket]['amount'] += (float) $invoice->reste_a_payer;
            $buckets[$bucket]['count'] += 1;
        }

        $total = array_sum(array_column($buckets, 'amount'));

        return collect($buckets)
            ->map(function (array $bucket) use ($total): array {
                $bucket['percentage'] = $total > 0 ? round(($bucket['amount'] / $total) * 100, 1) : 0.0;

                return $bucket;
            })
            ->values()
            ->all();
    }

    private function recoveryTone(float $recoveryRate): string
    {
        if ($recoveryRate >= 80) {
            return 'success';
        }

        if ($recoveryRate >= 50) {
            return 'primary';
        }

        return 'warning';
    }

    private function clientId(): int
    {
        $clientId = (int) Auth::user()?->client_id;

        abort_if($clientId <= 0, 403, 'Aucun client n’est associé à ce compte.');

        return $clientId;
    }
}
