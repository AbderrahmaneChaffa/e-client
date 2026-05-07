<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Jobs\VerifyImportJob;
use App\Models\ImportBatch;
use App\Services\ExcelTypeDetector;
use App\Services\ImportPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Bus, Cache, Schema, Storage};
use Illuminate\View\View;

class ImportController extends Controller
{
    private const ORDER = [
        'factures',
        'prestations',
        'paiements',
        'factures_payees',
        'prestations_payees',
    ];

    private const LEGACY_INPUTS = [
        'factures' => 'file_factures',
        'prestations' => 'file_prestations',
        'paiements' => 'file_paiements',
        'factures_payees' => 'file_factures_payees',
        'prestations_payees' => 'file_prestations_payees',
    ];

    public function index(): View
    {
        $query = ImportBatch::with('creator')->latest();

        if (Schema::hasTable('import_verifications')) {
            $query->withCount('verifications');
        }

        $batches = $query->paginate(20);

        return view('admins.imports.index', compact('batches'));
    }

    public function preview(Request $request, ImportPreviewService $previewService): JsonResponse
    {
        $request->validate($this->validationRules(), $this->validationMessages());

        $files = $this->allUploadedFiles($request);

        if ($files === []) {
            return response()->json(['message' => 'Veuillez selectionner au moins un fichier Excel.'], 422);
        }

        if (count($files) > 5) {
            return response()->json(['message' => 'Maximum 5 fichiers par import.'], 422);
        }

        try {
            return response()->json($previewService->previewMany(
                files: $files,
                forceImport: $request->boolean('force_import'),
            ));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request, ExcelTypeDetector $detector): JsonResponse
    {
        $request->validate($this->validationRules(), $this->validationMessages());

        try {
            $filesByType = $this->filesByType($request, $detector);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if ($filesByType === []) {
            return response()->json(['message' => 'Veuillez selectionner au moins un fichier Excel.'], 422);
        }

        $jobs = [];
        $batchIds = [];
        $forceImport = $request->boolean('force_import');

        foreach (self::ORDER as $type) {
            if (! isset($filesByType[$type])) {
                continue;
            }

            $file = $filesByType[$type];

            if ($file->getError() !== UPLOAD_ERR_OK) {
                return response()->json([
                    'message' => "Erreur upload [{$type}] : ".$this->uploadErrorMessage($file->getError()),
                ], 422);
            }

            $inspection = $detector->inspect($file, sampleRows: 2);
            if ($inspection['missing_headers'] !== []) {
                return response()->json([
                    'message' => "Colonnes manquantes pour {$file->getClientOriginalName()} : ".implode(', ', $inspection['missing_headers']),
                ], 422);
            }

            $path = $file->store("imports/{$type}", 'local');

            $batch = ImportBatch::create([
                'type' => $type,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'status' => 'pending',
                'force_import' => $forceImport,
                'metadata' => [
                    'detected_type' => $inspection['type'],
                    'confidence' => $inspection['confidence'],
                    'found_headers' => $inspection['found_headers'],
                    'uploaded_mode' => $request->hasFile('files') ? 'adaptive' : 'legacy',
                ],
                'created_by' => Auth::id(),
            ]);

            $batchIds[$type] = $batch->id;
            $jobs[] = new ProcessImportJob($batch->id);
        }

        if ($jobs !== []) {
            $lastBatchId = (int) array_values($batchIds)[count($batchIds) - 1];
            $jobs[] = new VerifyImportJob($lastBatchId, array_values($batchIds));

            Bus::chain($jobs)
                ->onQueue('imports')
                ->dispatch();
        }

        return response()->json([
            'batch_ids' => $batchIds,
            'message' => count($batchIds).' import(s) demarre(s) en sequence.',
        ]);
    }

    public function progress(ImportBatch $batch): JsonResponse
    {
        $cached = (int) Cache::get("import_batch_{$batch->id}", 0);
        $processed = max($cached, $batch->processed_rows);
        $total = $batch->total_rows;

        return response()->json([
            'status' => $batch->status,
            'processed' => $processed,
            'total' => $total,
            'failed' => $batch->failed_rows,
            'created' => $batch->created_rows,
            'updated' => $batch->updated_rows,
            'skipped' => $batch->skipped_rows,
            'percentage' => $total > 0 ? min(100, (int) round($processed / $total * 100)) : 0,
            'started_at' => $batch->started_at?->diffForHumans(),
            'completed_at' => $batch->completed_at?->format('d/m/Y H:i:s'),
            'type' => $batch->type,
            'verification' => Cache::get("import_verification_{$batch->id}", ['status' => 'pending']),
        ]);
    }

    public function resume(ImportBatch $batch): JsonResponse
    {
        if ($batch->status === 'processing') {
            return response()->json(['message' => 'Cet import est deja en cours.'], 422);
        }

        if (! Storage::disk('local')->exists($batch->stored_path)) {
            return response()->json(['message' => 'Le fichier source de cet import est introuvable.'], 422);
        }

        $batch->update([
            'status' => 'pending',
            'error_summary' => null,
            'completed_at' => null,
        ]);

        Bus::chain([
            new ProcessImportJob($batch->id),
            new VerifyImportJob($batch->id, [$batch->id]),
        ])->onQueue('imports')->dispatch();

        return response()->json(['message' => 'Reprise de l import lancee.', 'batch_id' => $batch->id]);
    }

    public function verifyGlobal(Request $request)
    {
        VerifyImportJob::dispatch(null, [])->onQueue('imports');

        if (! $request->expectsJson()) {
            return back()->with('status', 'Verification globale lancee en arriere-plan.');
        }

        return response()->json(['message' => 'Verification globale lancee en arriere-plan.']);
    }

    public function destroy(ImportBatch $batch): JsonResponse
    {
        if ($batch->status === 'processing') {
            return response()->json(['message' => 'Impossible de supprimer un import en cours de traitement.'], 422);
        }

        if (Storage::disk('local')->exists($batch->stored_path)) {
            Storage::disk('local')->delete($batch->stored_path);
        }

        $batch->delete();

        return response()->json(['message' => 'Import supprime.']);
    }

    public function templateFactures()
    {
        return redirect()->route('admin.imports.index');
    }

    public function templatePaiements()
    {
        return redirect()->route('admin.imports.index');
    }

    public function storeFactures(Request $request, ExcelTypeDetector $detector): JsonResponse
    {
        return $this->store($request, $detector);
    }

    public function storePaiements(Request $request, ExcelTypeDetector $detector): JsonResponse
    {
        return $this->store($request, $detector);
    }

    public function uploadTemp(Request $request, ImportPreviewService $previewService): JsonResponse
    {
        return $this->preview($request, $previewService);
    }

    private function validationRules(): array
    {
        return [
            'force_import' => ['nullable', 'boolean'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:xlsx,xls', 'max:102400'],
            'file_factures' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_prestations' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_paiements' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_factures_payees' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
            'file_prestations_payees' => ['nullable', 'file', 'mimes:xlsx,xls', 'max:102400'],
        ];
    }

    private function validationMessages(): array
    {
        return [
            '*.mimes' => 'Le fichier doit etre un Excel (.xlsx ou .xls).',
            '*.max' => 'Le fichier ne doit pas depasser 100 Mo.',
        ];
    }

    /**
     * @return array<int,\Illuminate\Http\UploadedFile>
     */
    private function allUploadedFiles(Request $request): array
    {
        $files = [];

        if ($request->hasFile('files')) {
            $uploaded = $request->file('files');
            $files = array_merge($files, is_array($uploaded) ? $uploaded : [$uploaded]);
        }

        foreach (self::LEGACY_INPUTS as $inputName) {
            if ($request->hasFile($inputName)) {
                $files[] = $request->file($inputName);
            }
        }

        return array_values(array_filter($files));
    }

    /**
     * @return array<string,\Illuminate\Http\UploadedFile>
     */
    private function filesByType(Request $request, ExcelTypeDetector $detector): array
    {
        $filesByType = [];

        if ($request->hasFile('files')) {
            foreach ((array) $request->file('files') as $file) {
                $type = $detector->detect($file);

                if (isset($filesByType[$type])) {
                    throw new \RuntimeException("Deux fichiers detectes comme {$type}. Gardez un seul fichier par type.");
                }

                $filesByType[$type] = $file;
            }
        }

        foreach (self::LEGACY_INPUTS as $type => $inputName) {
            if (! $request->hasFile($inputName)) {
                continue;
            }

            if (isset($filesByType[$type])) {
                throw new \RuntimeException("Deux fichiers fournis pour {$type}.");
            }

            $filesByType[$type] = $request->file($inputName);
        }

        return $filesByType;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (upload_max_filesize dans php.ini).',
            UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (MAX_FILE_SIZE du formulaire).',
            UPLOAD_ERR_PARTIAL => 'Fichier partiellement uploade.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier recu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur.',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d ecrire sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Upload bloque par une extension PHP.',
            default => "Erreur inconnue (code {$code}).",
        };
    }
}
