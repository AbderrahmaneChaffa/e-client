<?php
// app/Http/Controllers/Admin/ImportController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\ImportBatch;
use Illuminate\Bus\PendingChain;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Bus, Cache, Log};
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        $batches = ImportBatch::with('creator')
            ->latest()
            ->paginate(20);

        return view('admins.imports.index', compact('batches'));
    }

    /**
     * Upload simultané des 3 fichiers.
     * Chaque fichier est optionnel : on n'enqueue que ceux fournis,
     * dans l'ordre garanti factures → prestations → paiements.
     */
    public function store(Request $request): JsonResponse
    {
        // ── DEBUG TEMPORAIRE ─────────────────────────────────────────────
        Log::info('ImportController@store atteint', [
            'has_factures'    => $request->hasFile('file_factures'),
            'has_prestations' => $request->hasFile('file_prestations'),
            'has_paiements'   => $request->hasFile('file_paiements'),
            'content_length'  => $request->header('Content-Length'),
            'user_id'         => Auth::id(),
        ]);
        // ── FIN DEBUG ─────────────────────────────────────────────────────
        $request->validate([
            'file_factures' => [
                'nullable',
                'file',
                // Remplacer 'mimes:xlsx,xls' par 'mimetypes' avec tous les MIME Excel possibles
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'application/vnd.ms-excel,'
                    . 'application/octet-stream,'   // Certains exports ERP
                    . 'application/zip',             // xlsx est un zip
                'max:102400',
            ],
            'file_prestations' => [
                'nullable',
                'file',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'application/vnd.ms-excel,'
                    . 'application/octet-stream,'
                    . 'application/zip',
                'max:102400',
            ],
            'file_paiements' => [
                'nullable',
                'file',
                'mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,'
                    . 'application/vnd.ms-excel,'
                    . 'application/octet-stream,'
                    . 'application/zip',
                'max:102400',
            ],
        ], [
            'file_factures.mimetypes'    => 'Le fichier factures doit être un Excel (.xlsx ou .xls).',
            'file_prestations.mimetypes' => 'Le fichier prestations doit être un Excel (.xlsx ou .xls).',
            'file_paiements.mimetypes'   => 'Le fichier paiements doit être un Excel (.xlsx ou .xls).',
            'file_factures.max'          => 'Le fichier factures ne doit pas dépasser 100 Mo.',
            'file_prestations.max'       => 'Le fichier prestations ne doit pas dépasser 100 Mo.',
            'file_paiements.max'         => 'Le fichier paiements ne doit pas dépasser 100 Mo.',
        ]);

        // Au moins un fichier requis
        if (
            !$request->hasFile('file_factures')
            && !$request->hasFile('file_prestations')
            && !$request->hasFile('file_paiements')
        ) {
            return response()->json(
                ['message' => 'Veuillez sélectionner au moins un fichier.'],
                422
            );
        }

        // Ordre fixe : factures → prestations → paiements
        $typesEnOrdre = ['factures', 'prestations', 'paiements'];
        $jobs         = [];
        $batchIds     = [];

        foreach ($typesEnOrdre as $type) {
            $inputName = "file_{$type}";

            if (!$request->hasFile($inputName)) {
                continue;
            }

            $file  = $request->file($inputName);
            $path  = $file->store("imports/{$type}", 'local');

            $batch = ImportBatch::create([
                'type'              => $type,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path'       => $path,
                'status'            => 'pending',
                'created_by'        => Auth::id(),
            ]);

            $batchIds[$type] = $batch->id;
            $jobs[]          = new ProcessImportJob($batch->id);
        }

        // Bus::chain garantit l'ordre séquentiel :
        // le job suivant ne démarre que si le précédent réussit.
        if (!empty($jobs)) {
            Bus::chain($jobs)
                ->onQueue('imports')
                ->dispatch();
        }

        return response()->json([
            'batch_ids' => $batchIds,
            'message'   => count($jobs) . ' import(s) démarré(s) en séquence.',
        ]);
    }

    /**
     * Progression d'un batch unique (appelé pour chacun des 3 en parallèle).
     */
    public function progress(ImportBatch $batch): JsonResponse
    {
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
            'type'         => $batch->type,
        ]);
    }
}
