<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiques principales
        $stats = Facture::query()
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(total_ttc) as total_facture,
                SUM(montant_paye) as total_encaisse,
                SUM(reste_a_payer) as total_impayes
            ')
            ->first();

        $totalClients = Client::count();
        $totalUsers = User::count();
        $totalAdmins = User::where('role', 'admin')->count();
        $totalClientUsers = User::where('role', 'client')->count();

        // Taux de recouvrement
        $totalFactured = $stats->total_facture ?? 0;
        $totalPaid = $stats->total_encaisse ?? 0;
        $recoveryRate = $totalFactured > 0 ? ($totalPaid / $totalFactured) * 100 : 0;

        // Top 5 des clients les plus endettés
        $topDebiteurs = Client::withSum('factures', 'reste_a_payer')
            ->orderByDesc('factures_sum_reste_a_payer')
            ->take(5)
            ->get();

        // Top 5 meilleurs payeurs
        $topPayeurs = Client::withSum('factures', 'montant_paye')
            ->orderByDesc('factures_sum_montant_paye')
            ->take(5)
            ->get();

        // Données pour le graphique mensuel (6 derniers mois)
        $monthlyData = Paiement::selectRaw('MONTH(date_paiement) as mois, SUM(montant_verse) as total')
            ->where('date_paiement', '>=', now()->subMonths(6))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $labels = $monthlyData->map(fn($d) => date("M", mktime(0, 0, 0, $d->mois, 1)));
        $amounts = $monthlyData->pluck('total');

        // Factures payées vs impayées
        $paidInvoices = Facture::where('reste_a_payer', '<=', 0)->count();
        $unpaidInvoices = Facture::where('reste_a_payer', '>', 0)->count();
        $totalInvoices = $paidInvoices + $unpaidInvoices;

        // Données pour graphique circulaire (Factures)
        $invoiceChartData = [
            'paid' => $paidInvoices,
            'unpaid' => $unpaidInvoices
        ];

        // Derniers paiements
        $recentPayments = Paiement::with('facture.client')
            ->orderBy('date_paiement', 'desc')
            ->take(5)
            ->get();

        // Factures récentes
        $recentInvoices = Facture::with('client')
            ->orderBy('date_facture', 'desc')
            ->take(5)
            ->get();

        return view('admins.dashboard', compact(
            'stats',
            'totalClients',
            'totalUsers',
            'totalAdmins',
            'totalClientUsers',
            'recoveryRate',
            'topDebiteurs',
            'topPayeurs',
            'labels',
            'amounts',
            'paidInvoices',
            'unpaidInvoices',
            'totalInvoices',
            'invoiceChartData',
            'recentPayments',
            'recentInvoices'
        ));
    }
}

