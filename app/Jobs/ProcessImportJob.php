<?php
// app/Jobs/ProcessImportJob.php
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

    public int $timeout = 7200;
    public int $tries   = 1;

    public function __construct(private readonly int $batchId) {}

    public function handle(): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);

        // Si le batch précédent (dans la chaîne) a échoué, on abandonne
        if ($batch->status === 'failed') {
            return;
        }

        try {
            // ── Passe 1 : comptage léger ─────────────────────────────────
            $counter = new RowCountImport();
            Excel::import($counter, storage_path("app/private/{$batch->stored_path}"));

            $batch->update([
                'total_rows' => $counter->getCount(),
                'status'     => 'processing',
                'started_at' => now(),
            ]);

            // ── Passe 2 : import réel ────────────────────────────────────
            $import = match ($batch->type) {
                'factures'    => new FacturesImport($batch),
                'prestations' => new PrestationsImport($batch),
                'paiements'   => new PaiementsImport($batch),
                default       => throw new \InvalidArgumentException(
                    "Type d'import inconnu : {$batch->type}"
                ),
            };

            Excel::import($import, storage_path("app/private/{$batch->stored_path}"));

            // ── Log groupé des lignes ignorées (paiements) ───────────────
            if (method_exists($import, 'logMissingFactures')) {
                $import->logMissingFactures();
            }

            // ── Flush final du compteur cache ────────────────────────────
            $finalProcessed = (int) Cache::get("import_batch_{$batch->id}", 0);

            // Reste non flushé (localCount % FLUSH_EVERY)
            $remainder = $finalProcessed - $batch->processed_rows;

            $batch->update([
                'status'         => 'completed',
                'processed_rows' => $finalProcessed,
                // On n'écrase pas failed_rows si logMissingFactures l'a déjà incrémenté
                'completed_at'   => now(),
            ]);

            // Nettoyage du fichier temporaire après succès
            // (optionnel — à activer en production)
            // Storage::disk('local')->delete($batch->stored_path);

        } catch (\Throwable $e) {
            Log::error("Import EPO échoué [batch #{$batch->id} – {$batch->type}]", [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
            ]);

            $batch->update([
                'status'        => 'failed',
                'error_summary' => ['message' => $e->getMessage()],
                'completed_at'  => now(),
            ]);

            // On relance l'exception pour interrompre la chaîne Bus::chain
            // → les jobs suivants (prestations, paiements) ne s'exécuteront pas
            throw $e;
        } finally {
            Cache::forget("import_batch_{$batch->id}");
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportBatch::where('id', $this->batchId)->update([
            'status'        => 'failed',
            'completed_at'  => now(),
            'error_summary' => ['message' => $e->getMessage()],
        ]);
    }
}
