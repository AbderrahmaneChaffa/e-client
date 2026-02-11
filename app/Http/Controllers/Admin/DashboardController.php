<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Paiement;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // On récupère les stats en une seule passe sur la table factures
        $stats = Facture::query()
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(total_ttc) as total_facture,
                SUM(montant_paye) as total_encaisse,
                SUM(reste_a_payer) as total_impayes
            ')
            ->first();
        $totalClients = Client::count();

        // Top 5 des clients les plus endettés (pour l'EPO c'est critique)
        $topDebiteurs = Client::withSum('factures', 'reste_a_payer')
            ->orderByDesc('factures_sum_reste_a_payer')
            ->take(5)
            ->get();
        // Données pour le graphique (6 derniers mois)
        $monthlyData = Paiement::selectRaw('MONTH(date_paiement) as mois, SUM(montant_verse) as total')
            ->where('date_paiement', '>=', now()->subMonths(6))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $labels = $monthlyData->map(fn($d) => date("F", mktime(0, 0, 0, $d->mois, 1)));
        $amounts = $monthlyData->pluck('total');

        return view('admins.dashboard', compact('stats', 'totalClients', 'topDebiteurs', 'labels', 'amounts'));
    }
}
