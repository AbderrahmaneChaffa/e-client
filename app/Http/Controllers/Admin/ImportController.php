<?php
// app/Http/Controllers/Admin/ImportController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\ImportBatch;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Bus, Cache};
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        $batches = ImportBatch::with('creator')->latest()->paginate(20);
        return view('admins.imports.index', compact('batches'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file_factures'           => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_prestations'        => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_paiements'          => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_factures_payees'    => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_prestations_payees' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
        ]);

        $hasAny = collect([
            'file_factures', 'file_prestations', 'file_paiements',
            'file_factures_payees', 'file_prestations_payees',
        ])->some(fn($f) => $request->hasFile($f));

        if (!$hasAny) {
            return response()->json(
                ['message' => 'Veuillez sélectionner au moins un fichier.'],
                422
            );
        }

        // Ordre STRICT de la chaîne — ne pas modifier
        $ordreExecution = [
            'factures'           => 'file_factures',
            'prestations'        => 'file_prestations',
            'paiements'          => 'file_paiements',
            'factures_payees'    => 'file_factures_payees',
            'prestations_payees' => 'file_prestations_payees',
        ];

        $jobs     = [];
        $batchIds = [];

        foreach ($ordreExecution as $type => $inputName) {
            if (!$request->hasFile($inputName)) continue;

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

        if (!empty($jobs)) {
            Bus::chain($jobs)->onQueue('imports')->dispatch();
        }

        return response()->json([
            'batch_ids' => $batchIds,
            'message'   => count($jobs) . ' import(s) démarré(s) en séquence.',
        ]);
    }

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