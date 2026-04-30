<?php
// app/Jobs/ProcessImportJob.php
namespace App\Jobs;

use App\Imports\{
    FacturesImport,
    FacturesPayeesImport,
    PaiementsImport,
    PrestationsImport,
    PrestationsPayeesImport,
    RowCountImport,
};
use App\Models\ImportBatch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Cache, DB, Log};
use Maatwebsite\Excel\Facades\Excel;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(private readonly int $batchId)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1G');
        $batch = ImportBatch::findOrFail($this->batchId);

        if ($batch->status === 'failed')
            return;

        try {
            // ── Optimisations MySQL session-level ────────────────────────────
          //  DB::statement('SET SESSION innodb_flush_log_at_trx_commit = 0');
            DB::statement('SET SESSION foreign_key_checks = 0');
            DB::statement('SET SESSION unique_checks = 0');      // ← désactive checks unicité
            DB::statement('SET SESSION sql_log_bin = 0');        // ← désactive binary log

            // Passe 1 : comptage
            $counter = new RowCountImport();
            Excel::import($counter, storage_path("app/private/{$batch->stored_path}"));

            $batch->update([
                'total_rows' => $counter->getCount(),
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Passe 2 : import réel
            $import = match ($batch->type) {
                'factures' => new FacturesImport($batch),
                'prestations' => new PrestationsImport($batch),
                'paiements' => new PaiementsImport($batch),
                'factures_payees' => new FacturesPayeesImport($batch),
                'prestations_payees' => new PrestationsPayeesImport($batch),
                default => throw new \InvalidArgumentException("Type inconnu : {$batch->type}"),
            };

            Excel::import($import, storage_path("app/private/{$batch->stored_path}"));

            if (method_exists($import, 'logMissingFactures')) {
                $import->logMissingFactures();
            }

            $finalProcessed = (int) Cache::get("import_batch_{$batch->id}", 0);

            $batch->update([
                'status' => 'completed',
                'processed_rows' => $finalProcessed,
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error("Import EPO échoué [batch #{$batch->id} – {$batch->type}]", [
                'message' => $e->getMessage(),
                'line' => $e->getFile() . ':' . $e->getLine(),
            ]);

            $batch->update([
                'status' => 'failed',
                'error_summary' => ['message' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            throw $e; // Interrompt la chaîne Bus::chain

        } finally {
            // ── Réactiver après l'import ─────────────────────────────────────
            DB::statement('SET SESSION foreign_key_checks = 1');
            DB::statement('SET SESSION unique_checks = 1');
          //  DB::statement('SET SESSION innodb_flush_log_at_trx_commit = 1');
            Cache::forget("import_batch_{$batch->id}");
        }
    }

    public function failed(\Throwable $e): void
    {
        ImportBatch::where('id', $this->batchId)->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_summary' => ['message' => $e->getMessage()],
        ]);
    }
}