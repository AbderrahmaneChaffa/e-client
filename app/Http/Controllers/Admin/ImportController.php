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

    public function store(Request $request)
    {
        $request->validate(['file' => 'required']);

        // REMPLACEZ Excel::queueImport PAR Excel::import pour tester en direct
        Excel::import(new FacturesImport, $request->file('file'));

        return back()->with('success', 'Importation réussie !');
    }
}
