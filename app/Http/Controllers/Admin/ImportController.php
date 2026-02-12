<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\FacturesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function index()
    {
        return view('admins.imports.index');
    }

    // public function store(Request $request)
    // {
    //     $request->validate(['file' => 'required']);

    //     // REMPLACEZ Excel::queueImport PAR Excel::import pour tester en direct
    //     Excel::import(new FacturesImport, $request->file('file'));

    //     return back()->with('success', 'Importation réussie !');
    // }
    // ImportController.php

    // A. Upload le fichier dans un dossier temporaire
    public function uploadTemp(Request $request)
    {
        $path = $request->file('file')->store('temp-imports');
        return response()->json(['path' => $path]);
    }

    // B. Scan rapide sans importer
    public function analyze(Request $request)
    {
        $path = storage_path('app/' . $request->path);
        $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $path)[0];

        $total = count($data) - 1; // -1 pour l'en-tête
        $duplicates = 0;

        foreach (array_slice($data, 1) as $row) {
            // On vérifie si le numéro de facture existe déjà
            if (\App\Models\Facture::where('numero_facture', $row[0])->exists()) {
                $duplicates++;
            }
        }

        return response()->json(['total' => $total, 'duplicates' => $duplicates]);
    }

    // C. Lancer le Job final
    public function store(Request $request)
    {
        $path = $request->file_path;
        Excel::queueImport(new FacturesImport, storage_path('app/' . $path));
        return redirect()->route('admin.factures.index')->with('success', 'Importation lancée en arrière-plan !');
    }
}
