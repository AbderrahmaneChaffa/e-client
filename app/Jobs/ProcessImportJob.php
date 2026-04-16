<?php

namespace App\Jobs;

use App\Imports\{FacturesImport, PaiementsImport, PrestationsImport, RowCountImport};
use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Cache, Log};
use Maatwebsite\Excel\Facades\Excel;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;  // 2 heures max
    public int $tries   = 1;     // Pas de retry automatique sur un import partiel

    public function __construct(private readonly int $batchId) {}

    public function handle(): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);

        try {
            // ── Passe 1 : comptage ───────────────────────────────────────────
            $counter = new RowCountImport();
            Excel::import($counter, storage_path("app/private/{$batch->stored_path}"));

            $batch->update([
                'total_rows' => $counter->getCount(),
                'status'     => 'processing',
                'started_at' => now(),
            ]);

            // ── Passe 2 : import réel ────────────────────────────────────────
            $import = match ($batch->type) {
                'factures'    => new FacturesImport($batch),
                'prestations' => new PrestationsImport($batch),
                'paiements'   => new PaiementsImport($batch),
                default       => throw new \InvalidArgumentException("Type inconnu : {$batch->type}"),
            };

            Excel::import($import, storage_path("app/private/{$batch->stored_path}"));

            // ── Log groupé des factures manquantes (paiements uniquement) ────
            // Évite 18 000 appels à batch->increment('failed_rows') pendant l'import
            if ($import instanceof PaiementsImport) {
                $import->logMissingFactures();
            }

            // ── Flush final ──────────────────────────────────────────────────
            $finalCount = (int) Cache::get("import_batch_{$batch->id}", 0);

            $batch->update([
                'status'         => 'completed',
                'processed_rows' => $finalCount,
                'completed_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("Import EPO échoué [batch #{$batch->id}]", [
                'message' => $e->getMessage(),
            ]);

            $batch->update([
                'status'        => 'failed',
                'error_summary' => ['message' => $e->getMessage()],
                'completed_at'  => now(),
            ]);

            throw $e;
        } finally {
            Cache::forget("import_batch_{$batch->id}");
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportBatch::where('id', $this->batchId)->update([
            'status'       => 'failed',
            'completed_at' => now(),
        ]);
    }
}
