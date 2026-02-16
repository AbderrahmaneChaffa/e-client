<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Imports\FacturesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function index()
    {
        return view('admins.imports.index');
    }

    public function store(Request $request)
    {
        // 1. Validation stricte
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200', // Max 50Mo
        ]);

        try {
            // 2. Lancement du Job en Queue
            Excel::queueImport(new FacturesImport, $request->file('file'));

            // 3. Réponse JSON pour le script JS
            return response()->json([
                'success' => true,
                'message' => 'Fichier reçu à 100%. Le traitement  démarre en arrière-plan.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur Import: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Erreur serveur : " . $e->getMessage()
            ], 500);
        }
    }
}
