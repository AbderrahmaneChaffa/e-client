<?php


// app/Http/Controllers/Admin/ImportController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\ImportBatch;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        $batches = ImportBatch::with('creator')
            ->latest()
            ->paginate(15);

        return view('admins.imports.index', compact('batches'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:factures,prestations,paiements'],
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:102400'], // 100 Mo
        ]);

        $file = $request->file('file');
        $path = $file->store("imports/{$request->type}", 'local');

        $batch = ImportBatch::create([
            'type'              => $request->type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $path,
            'status'            => 'pending',
            'created_by'        => Auth::id(),
        ]);

        ProcessImportJob::dispatch($batch->id)->onQueue('imports');

        return response()->json([
            'batch_id' => $batch->id,
            'message'  => "Import «{$request->type}» démarré en arrière-plan.",
        ]);
    }

    public function progress(ImportBatch $batch): JsonResponse
    {
        // Lecture du cache d'abord (mis à jour en temps réel par le job)
        $cached    = (int) Cache::get("import_batch_{$batch->id}", 0);
        $processed = max($cached, $batch->processed_rows);
        $total     = $batch->total_rows;

        return response()->json([
            'status'       => $batch->status,
            'processed'    => $processed,
            'total'        => $total,
            'failed'       => $batch->failed_rows,
            'percentage'   => $total > 0 ? min(100, (int) round($processed / $total * 100)) : 0,
            'started_at'   => $batch->started_at?->diffForHumans(),
            'completed_at' => $batch->completed_at?->format('d/m/Y H:i:s'),
        ]);
    }
}



// namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Imports\FacturesImport;
// use App\Imports\PaiementsImport;
// use Maatwebsite\Excel\Facades\Excel;
// use Illuminate\Support\Facades\Log;

// class ImportController extends Controller
// {
//     public function index()
//     {
//         return view('admins.imports.index');
//     }

//     public function templateFactures()
//     {
//         $headers = ['Content-Type' => 'text/csv'];
//         $callback = function () {
//             $handle = fopen('php://output', 'w');
//             fputcsv($handle, ['n_facture','date','client_code','client_nom','nis','t_ht','t_tva','t_ttc']);
//             fclose($handle);
//         };
//         return response()->stream($callback, 200, $headers);
//     }

//     public function templatePaiements()
//     {
//         $headers = ['Content-Type' => 'text/csv'];
//         $callback = function () {
//             $handle = fopen('php://output', 'w');
//             fputcsv($handle, ['n_facture','date','reference','numero_cheque','banque','montant']);
//             fclose($handle);
//         };
//         return response()->stream($callback, 200, $headers);
//     }

//     public function storeFactures(Request $request)
//     {
//         $request->validate([
//             'file' => 'required|mimes:xlsx,xls,csv|max:51200',
//         ]);

//         try {
//             Excel::queueImport(new FacturesImport, $request->file('file'));

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Fichier factures reçu à 100%. Traitement en arrière-plan.'
//             ]);
//         } catch (\Exception $e) {
//             Log::error("Erreur Import Factures: " . $e->getMessage());
//             return response()->json([
//                 'success' => false,
//                 'message' => "Erreur serveur : " . $e->getMessage()
//             ], 500);
//         }
//     }

//     public function storePaiements(Request $request)
//     {
//         $request->validate([
//             'file' => 'required|mimes:xlsx,xls,csv|max:51200',
//         ]);

//         try {
//             Excel::queueImport(new PaiementsImport, $request->file('file'));

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Fichier paiements reçu à 100%. Traitement en arrière-plan.'
//             ]);
//         } catch (\Exception $e) {
//             Log::error("Erreur Import Paiements: " . $e->getMessage());
//             return response()->json([
//                 'success' => false,
//                 'message' => "Erreur serveur : " . $e->getMessage()
//             ], 500);
//         }
//     }
// }
