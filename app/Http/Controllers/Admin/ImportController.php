<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\FacturesImport;
use App\Imports\PaiementsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function index()
    {
        return view('admins.imports.index');
    }

    public function templateFactures()
    {
        $headers = ['Content-Type' => 'text/csv'];
        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['n_facture','date','client_code','client_nom','nis','t_ht','t_tva','t_ttc']);
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function templatePaiements()
    {
        $headers = ['Content-Type' => 'text/csv'];
        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['n_facture','date','reference','numero_cheque','banque','montant']);
            fclose($handle);
        };
        return response()->stream($callback, 200, $headers);
    }

    public function storeFactures(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200',
        ]);

        try {
            Excel::queueImport(new FacturesImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Fichier factures reçu à 100%. Traitement en arrière-plan.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur Import Factures: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erreur serveur : " . $e->getMessage()
            ], 500);
        }
    }

    public function storePaiements(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200',
        ]);

        try {
            Excel::queueImport(new PaiementsImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Fichier paiements reçu à 100%. Traitement en arrière-plan.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur Import Paiements: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erreur serveur : " . $e->getMessage()
            ], 500);
        }
    }
}
