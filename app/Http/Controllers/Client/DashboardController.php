<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $clientId = $user->client_id;

        // Totaux factures
        $totalInvoices = Facture::where('client_id', $clientId)->count();
        $totalFactured = Facture::where('client_id', $clientId)->sum('total_ttc');
        $totalPaid = Facture::where('client_id', $clientId)->sum('montant_paye');
        $totalDue = Facture::where('client_id', $clientId)->sum('reste_a_payer');

        // Récents
        $recentInvoices = Facture::where('client_id', $clientId)
            ->orderBy('date_facture', 'desc')
            ->take(5)
            ->get();

        $recentPayments = Paiement::whereHas('facture', function ($q) use ($clientId) {
            $q->where('client_id', $clientId);
        })->orderBy('date_paiement', 'desc')
            ->take(5)
            ->get();

        // Statut factures pour graphique
        $paidInvoices = Facture::where('client_id', $clientId)
            ->where('reste_a_payer', '<=', 0)->count();
        $unpaidInvoices = Facture::where('client_id', $clientId)
            ->where('reste_a_payer', '>', 0)->count();
        $totalInv = $paidInvoices + $unpaidInvoices;

        $invoiceChartData = ['paid' => $paidInvoices, 'unpaid' => $unpaidInvoices];

        // Paiements mensuels (6 derniers mois)
        $monthlyData = Paiement::selectRaw('MONTH(date_paiement) as mois, SUM(montant_verse) as total')
            ->whereHas('facture', fn($q) => $q->where('client_id', $clientId))
            ->where('date_paiement', '>=', now()->subMonths(6))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $labels = $monthlyData->map(fn($d) => date('M', mktime(0, 0, 0, $d->mois, 1)));
        $amounts = $monthlyData->pluck('total');

        return view('clients.dashboard', compact(
            'totalInvoices',
            'totalFactured',
            'totalPaid',
            'totalDue',
            'recentInvoices',
            'recentPayments',
            'invoiceChartData',
            'labels',
            'amounts'
        ));
    }
}
