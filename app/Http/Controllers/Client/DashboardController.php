<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $clientId = $user->client_id;

        // 1. Calcul des totaux en UNE SEULE requête (Optimisation Performance)
        // On exclut strictement les factures annulées
        $stats = Facture::where('client_id', $clientId)
            ->where('annuler', false)
            ->selectRaw('
                COUNT(*) as count, 
                SUM(total_ttc) as total_factured, 
                SUM(montant_paye) as total_paid, 
                SUM(reste_a_payer) as total_due
            ')
            ->first();

        // 2. Factures récentes avec relations (Escale par exemple)
        $recentInvoices = Facture::with('escale') // Eager loading
            ->where('client_id', $clientId)
            ->where('annuler', false)
            ->orderBy('date_facture', 'desc')
            ->take(5)
            ->get();

        // 3. Paiements récents
        $recentPayments = Paiement::whereHas('facture', function ($q) use ($clientId) {
            $q->where('client_id', $clientId)->where('annuler', false);
        })
            ->orderBy('date_paiement', 'desc')
            ->take(5)
            ->get();

        // 4. Données pour le graphique de statut (Donut Chart)
        $paidCount = Facture::where('client_id', $clientId)
            ->where('annuler', false)
            ->where('reste_a_payer', '<=', 0)
            ->count();

        $unpaidCount = ($stats->count ?? 0) - $paidCount;

        // 5. Paiements mensuels (6 derniers mois) - Correction du tri par année/mois
        $monthlyData = Paiement::selectRaw('YEAR(date_paiement) as annee, MONTH(date_paiement) as mois, SUM(montant) as total')
            ->whereHas('facture', fn($q) => $q->where('client_id', $clientId)->where('annuler', false))
            ->where('date_paiement', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'asc')
            ->orderBy('mois', 'asc')
            ->get();

        $labels = $monthlyData->map(function ($d) {
            return Carbon::create($d->annee, $d->mois, 1)->translatedFormat('M Y');
        });
        $amounts = $monthlyData->pluck('total');

        return view('clients.dashboard', [
            'totalInvoices'    => $stats->count ?? 0,
            'totalFactured'    => $stats->total_factured ?? 0,
            'totalPaid'        => $stats->total_paid ?? 0,
            'totalDue'         => $stats->total_due ?? 0,
            'recentInvoices'   => $recentInvoices,
            'recentPayments'   => $recentPayments,
            'invoiceChartData' => ['paid' => $paidCount, 'unpaid' => $unpaidCount],
            'labels'           => $labels,
            'amounts'          => $amounts,
        ]);
    }
}
