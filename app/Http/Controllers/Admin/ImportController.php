<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImportJob;
use App\Jobs\VerifyImportJob;
use App\Models\ImportBatch;
use App\Services\ExcelTypeDetector;
use App\Services\ImportPreviewService;
use App\Services\ImportVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Bus, Cache, Log, Schema, Storage};
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

    public function index(Request $request): View
    {
        $query = ImportBatch::with('creator')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if (Schema::hasTable('import_verifications')) {
            $query->withCount('verifications');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $batches = $query->paginate($perPage)->withQueryString();

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

            $fileHash = sha1_file($file->getRealPath()) ?: null;
            $path = $file->store("imports/{$type}", 'local');

            $batch = ImportBatch::create([
                'type' => $type,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'file_hash' => $fileHash,
                'status' => 'pending',
                'force_import' => $forceImport,
                'metadata' => [
                    'detected_type' => $inspection['type'],
                    'confidence' => $inspection['confidence'],
                    'found_headers' => $inspection['found_headers'],
                    'file_hash' => $fileHash,
                    'file_size' => $file->getSize(),
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
        return response()->json($this->progressPayload($batch->fresh()));
    }

    public function progressMany(Request $request): JsonResponse
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $progress = ImportBatch::query()
            ->when($ids !== [], fn ($query) => $query->whereIn('id', $ids), fn ($query) => $query->whereRaw('1 = 0'))
            ->get()
            ->mapWithKeys(fn (ImportBatch $batch) => [$batch->id => $this->progressPayload($batch)])
            ->all();

        $history = ImportBatch::with('creator')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (ImportBatch $batch) => $this->historyPayload($batch))
            ->values()
            ->all();

        return response()->json([
            'progress' => $progress,
            'history' => $history,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function progressPayload(ImportBatch $batch): array
    {
        $cached = (int) Cache::get("import_batch_{$batch->id}", 0);
        $processed = max($cached, (int) $batch->processed_rows);
        $total = (int) $batch->total_rows;
        $elapsed = $batch->started_at ? max(1, now()->diffInSeconds($batch->started_at)) : 0;
        $rate = $elapsed > 0 && $processed > 0 ? $processed / $elapsed : 0;
        $remaining = max(0, $total - $processed);
        $etaSeconds = $rate > 0 && $batch->status === 'processing' ? (int) ceil($remaining / $rate) : null;

        return [
            'id' => $batch->id,
            'status' => $batch->status,
            'processed' => $processed,
            'total' => $total,
            'failed' => $batch->failed_rows,
            'created' => $batch->created_rows,
            'updated' => $batch->updated_rows,
            'skipped' => $batch->skipped_rows,
            'percentage' => $total > 0 ? min(100, (int) round($processed / $total * 100)) : ($batch->status === 'completed' ? 100 : 0),
            'started_at' => $batch->started_at?->diffForHumans(),
            'completed_at' => $batch->completed_at?->format('d/m/Y H:i:s'),
            'elapsed_seconds' => $elapsed,
            'rows_per_second' => $rate > 0 ? round($rate, 2) : 0,
            'eta_seconds' => $etaSeconds,
            'eta' => $etaSeconds !== null ? $this->humanDuration($etaSeconds) : null,
            'type' => $batch->type,
            'sync_mode' => data_get($batch->metadata, 'sync_mode'),
            'skipped_duplicate_of' => data_get($batch->metadata, 'skipped_duplicate_of'),
            'verification' => Cache::get("import_verification_{$batch->id}", ['status' => 'pending']),
        ];
    }

    private function historyPayload(ImportBatch $batch): array
    {
        $progress = $this->progressPayload($batch);

        return [
            ...$progress,
            'filename' => $batch->original_filename,
            'type_label' => $this->typeLabel($batch->type),
            'status_label' => $this->statusLabel($batch->status),
            'created_date' => $batch->created_at?->format('d/m/Y'),
            'created_time' => $batch->created_at?->format('H:i'),
            'creator' => $batch->creator?->name ?? '-',
            'can_delete' => in_array($batch->status, ['completed', 'failed', 'pending'], true),
        ];
    }

    private function typeLabel(string $type): string
    {
        return [
            'factures' => 'Factures',
            'prestations' => 'Prestations',
            'paiements' => 'Paiements',
            'factures_payees' => 'Factures Payees',
            'prestations_payees' => 'Prestations Payees',
        ][$type] ?? ucfirst($type);
    }

    private function statusLabel(string $status): string
    {
        return [
            'pending' => 'En attente',
            'processing' => 'En cours',
            'completed' => 'Termine',
            'failed' => 'Echec',
        ][$status] ?? $status;
    }

    private function humanDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes.'m '.str_pad((string) $remainingSeconds, 2, '0', STR_PAD_LEFT).'s';
        }

        $hours = intdiv($minutes, 60);

        return $hours.'h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT).'m';
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
        $startedAt = now();
        $lockTtl = now()->addHours(2);

        if (! Cache::add(VerifyImportJob::GLOBAL_LOCK_CACHE_KEY, [
            'started_at' => $startedAt->toIso8601String(),
            'user_id' => Auth::id(),
        ], $lockTtl)) {
            $status = Cache::get(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY, ['status' => 'processing']);
            $message = 'Une verification globale est deja en cours.';

            if (! $request->expectsJson()) {
                return back()->with('error', $message);
            }

            return response()->json([
                'message' => $message,
                'status' => $status,
            ], 409);
        }

        Cache::put(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY, [
            'status' => 'queued',
            'message' => 'Verification globale ajoutee a la file.',
            'percentage' => 0,
            'started_at' => $startedAt->toIso8601String(),
            'updated_at' => $startedAt->toIso8601String(),
            'user_id' => Auth::id(),
        ], now()->addHours(2));

        try {
            VerifyImportJob::dispatch(null, [])->onQueue('imports');
        } catch (\Throwable $e) {
            Cache::forget(VerifyImportJob::GLOBAL_LOCK_CACHE_KEY);
            Cache::put(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
                'user_id' => Auth::id(),
            ], now()->addDay());

            Log::channel('imports')->error('Global import verification dispatch failed', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return back()->with('error', 'Impossible de lancer la verification globale: '.$e->getMessage());
            }

            return response()->json(['message' => $e->getMessage()], 500);
        }

        Log::channel('imports')->info('Global import verification dispatched', [
            'user_id' => Auth::id(),
            'queued_at' => $startedAt->toIso8601String(),
        ]);

        if (! $request->expectsJson()) {
            return back()->with('success', 'Verification globale lancee en arriere-plan.');
        }

        return response()->json([
            'message' => 'Verification globale lancee en arriere-plan.',
            'status' => Cache::get(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY),
        ]);
    }

    public function verifyGlobalStatus(ImportVerificationService $verifier): JsonResponse
    {
        $status = Cache::get(VerifyImportJob::GLOBAL_STATUS_CACHE_KEY, [
            'status' => 'idle',
            'message' => null,
            'percentage' => null,
        ]);

        return response()->json([
            'status' => $status,
            'health' => $verifier->latestHealthSummary(),
            'server_time' => now()->toIso8601String(),
        ]);
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
