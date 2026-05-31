<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Client\PaiementsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class PaiementController extends Controller
{
    public function index(Request $request)
    {
        $clientId = Auth::user()->client_id;

        $query = Paiement::with('facture')
            ->whereHas('facture', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });

        // 🔍 Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recu', 'like', '%' . $search . '%')
                    ->orWhere('numero_cheque', 'like', '%' . $search . '%')
                    ->orWhereHas('facture', fn($invoice) => $invoice->where('numero_facture', 'like', '%' . $search . '%'));
            });
        }

        if ($request->filled('banque')) {
            $query->where('banque', $request->banque);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_paiement', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_paiement', '<=', $request->date_to);
        }

        // 📊 Tri
        $allowedSorts = ['date_paiement', 'montant', 'recu', 'banque', 'numero_cheque'];
        $sortBy = in_array($request->input('sort'), $allowedSorts, true) ? $request->input('sort') : 'date_paiement';
        $sortDir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) $request->input('per_page', 25), 100);

        // 📦 Récupération des paiements (Collection)
        $paiements = $query->orderBy($sortBy, $sortDir)->get();

        // 🎯 GROUPER PAR numero_cheque
        $groupedPaiements = $paiements->groupBy(function ($paiement) {
            return $paiement->numero_cheque ?? 'sans_cheque_' . $paiement->id;
        });

        // 📄 Pagination manuelle sur la collection groupée
        $currentPage = $request->input('page', 1);
        $paginatedGroups = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedPaiements->forPage($currentPage, $perPage),
            $groupedPaiements->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => 'page'
            ]
        );

        // 📈 Stats
        $totalPaiements = $paiements->count();
        $totalMontant = $paiements->sum('montant');
        $totalCheques = $groupedPaiements->count();

        // ✅ CORRECTION : passer TOUS les variables nécessaires à la view
        return view('clients.paiements.index', compact(
            'paiements',        // 👈 Collection originale (pour compatibilité view)
            'paginatedGroups',  // 👈 Pour l'affichage groupé paginé
            'totalPaiements',
            'totalMontant',
            'totalCheques'
        ));
    }

    // 📤 Export Excel
    public function exportExcel(Request $request)
    {
        $clientId = Auth::user()->client_id;

        $query = Paiement::with('facture')
            ->whereHas('facture', fn($q) => $q->where('client_id', $clientId));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recu', 'like', '%' . $search . '%')
                    ->orWhere('numero_cheque', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('banque'))
            $query->where('banque', $request->banque);
        if ($request->filled('date_from'))
            $query->whereDate('date_paiement', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $query->whereDate('date_paiement', '<=', $request->date_to);

        $paiements = $query->orderBy('numero_cheque', 'asc')->orderBy('date_paiement', 'desc')->get();

        return Excel::download(
            new PaiementsExport($paiements),
            'paiements_' . date('Y-m-d') . '.xlsx'
        );
    }

    // 📄 Export PDF
    public function exportPdf(Request $request)
    {
        $clientId = Auth::user()->client_id;

        $query = Paiement::with('facture.client')
            ->whereHas('facture', fn($q) => $q->where('client_id', $clientId));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('recu', 'like', '%' . $search . '%')
                    ->orWhere('numero_cheque', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('banque'))
            $query->where('banque', $request->banque);
        if ($request->filled('date_from'))
            $query->whereDate('date_paiement', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $query->whereDate('date_paiement', '<=', $request->date_to);

        $paiements = $query->orderBy('numero_cheque', 'asc')->orderBy('date_paiement', 'desc')->get();
        $groupedPaiements = $paiements->groupBy(fn($p) => $p->numero_cheque ?? 'sans_cheque_' . $p->id);

        $pdf = Pdf::loadView('clients.paiements.exports.pdf', [
            'groupedPaiements' => $groupedPaiements,
            'totalMontant' => $paiements->sum('montant'),
            'dateExport' => now()->format('d/m/Y H:i')
        ]);

        return $pdf->download('paiements_' . date('Y-m-d') . '.pdf');
    }

    // 🖨️ Impression
    public function print(Request $request)
    {
        return $this->index($request);
    }
}