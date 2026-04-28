<?php
// app/Http/Controllers/Admin/ImportController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Models\ImportBatch;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Bus, Cache, Storage};
use Illuminate\View\View;

class ImportController extends Controller
{
    // Ordre strict — ne jamais modifier
    private const ORDRE_EXECUTION = [
        'factures' => 'file_factures',
        'prestations' => 'file_prestations',
        'paiements' => 'file_paiements',
        'factures_payees' => 'file_factures_payees',
        'prestations_payees' => 'file_prestations_payees',
    ];

    public function index(): View
    {
        $batches = ImportBatch::with('creator')
            ->latest()
            ->paginate(20);

        return view('admins.imports.index', compact('batches'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(
            [
                'file_factures' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
                'file_prestations' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
                'file_paiements' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
                'file_factures_payees' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
                'file_prestations_payees' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            ],
            [
                '*.mimes' => 'Le fichier doit être un Excel (.xlsx ou .xls).',
                '*.max' => 'Le fichier ne doit pas dépasser 100 Mo.',
            ]
        );

        // Vérifier qu'au moins un fichier est envoyé
        $hasAny = collect(array_values(self::ORDRE_EXECUTION))
            ->some(fn($field) => $request->hasFile($field));

        if (!$hasAny) {
            return response()->json(
                ['message' => 'Veuillez sélectionner au moins un fichier Excel.'],
                422
            );
        }

        $jobs = [];
        $batchIds = [];

        foreach (self::ORDRE_EXECUTION as $type => $inputName) {
            if (!$request->hasFile($inputName)) {
                continue;
            }

            $file = $request->file($inputName);

            // Vérification PHP de l'upload (protection contre UPLOAD_ERR_*)
            if ($file->getError() !== UPLOAD_ERR_OK) {
                return response()->json([
                    'message' => "Erreur upload [{$type}] : " . $this->uploadErrorMessage($file->getError()),
                ], 422);
            }

            $path = $file->store("imports/{$type}", 'local');

            $batch = ImportBatch::create([
                'type' => $type,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            $batchIds[$type] = $batch->id;
            $jobs[] = new ProcessImportJob($batch->id);
        }

        if (!empty($jobs)) {
            Bus::chain($jobs)
                ->onQueue('imports')
                ->dispatch();
        }

        return response()->json([
            'batch_ids' => $batchIds,
            'message' => count($jobs) . ' import(s) démarré(s) en séquence.',
        ]);
    }

    public function progress(ImportBatch $batch): JsonResponse
    {
        // Lecture du compteur cache (mis à jour en temps réel par le job)
        $cached = (int) Cache::get("import_batch_{$batch->id}", 0);
        $processed = max($cached, $batch->processed_rows);
        $total = $batch->total_rows;

        return response()->json([
            'status' => $batch->status,
            'processed' => $processed,
            'total' => $total,
            'failed' => $batch->failed_rows,
            'percentage' => $total > 0 ? min(100, (int) round($processed / $total * 100)) : 0,
            'started_at' => $batch->started_at?->diffForHumans(),
            'completed_at' => $batch->completed_at?->format('d/m/Y H:i:s'),
            'type' => $batch->type,
        ]);
    }

    /**
     * Supprime un batch et son fichier physique.
     */
    public function destroy(ImportBatch $batch): JsonResponse
    {
        // Empêcher la suppression d'un job en cours
        if ($batch->status === 'processing') {
            return response()->json(
                ['message' => 'Impossible de supprimer un import en cours de traitement.'],
                422
            );
        }

        // Supprimer le fichier Excel stocké
        if (Storage::disk('local')->exists($batch->stored_path)) {
            Storage::disk('local')->delete($batch->stored_path);
        }

        $batch->delete();

        return response()->json(['message' => 'Import supprimé.']);
    }

    // ── Helper privé ─────────────────────────────────────────────────────────
    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (upload_max_filesize dans php.ini).',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (MAX_FILE_SIZE du formulaire).',
            UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploadé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier reçu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur.',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqué par une extension PHP.',
            default => "Erreur inconnue (code {$code}).",
        };
    }
}