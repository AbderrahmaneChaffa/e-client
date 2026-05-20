<?php

namespace App\Jobs;

use App\Imports\FacturesImport;
use App\Imports\FacturesPayeesImport;
use App\Imports\PaiementsImport;
use App\Imports\PrestationsImport;
use App\Imports\PrestationsPayeesImport;
use App\Models\Client;
use App\Models\Escale;
use App\Models\Facture;
use App\Models\ImportBatch;
use App\Models\Navire;
use App\Models\Paiement;
use App\Models\Prestation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Telescope\Telescope;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(private readonly int $batchId)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '2G');

        $batch = ImportBatch::findOrFail($this->batchId);
        $timings = [];
        $startedAt = microtime(true);
        $eventDispatchers = [];
        $wasTelescopeRecording = class_exists(Telescope::class) ? Telescope::isRecording() : false;

        if ($batch->status === 'failed') {
            return;
        }

        try {
            if ($this->completeFromPreviousIdenticalImport($batch)) {
                return;
            }

            $this->optimizeDatabaseSession();
            $eventDispatchers = $this->disableModelEvents();

            if (class_exists(Telescope::class)) {
                Telescope::stopRecording();
            }

            $path = storage_path("app/private/{$batch->stored_path}");

            $timings['start'] = microtime(true) - $startedAt;
            $totalRows = $this->countRowsFast($path);
            $timings['after_count'] = microtime(true) - $startedAt;

            $batch->update([
                'total_rows' => $totalRows,
                'processed_rows' => 0,
                'failed_rows' => 0,
                'created_rows' => 0,
                'updated_rows' => 0,
                'skipped_rows' => 0,
                'status' => 'processing',
                'started_at' => now(),
                'completed_at' => null,
            ]);

            Log::channel('imports')->info('Import started', [
                'batch_id' => $batch->id,
                'type' => $batch->type,
                'rows' => $totalRows,
                'force_import' => $batch->force_import,
            ]);

            $import = match ($batch->type) {
                'factures' => new FacturesImport($batch),
                'prestations' => new PrestationsImport($batch),
                'paiements' => new PaiementsImport($batch),
                'factures_payees' => new FacturesPayeesImport($batch),
                'prestations_payees' => new PrestationsPayeesImport($batch),
                default => throw new \InvalidArgumentException("Type inconnu : {$batch->type}"),
            };

            Excel::import($import, $path);
            $timings['after_import'] = microtime(true) - $startedAt;

            Log::channel('imports')->info("TIMING [batch #{$batch->id}] {$batch->type}", $timings);

            if (method_exists($import, 'logMissingFactures')) {
                $import->logMissingFactures();
            }

            $freshBatch = $batch->fresh();
            $finalProcessed = max(
                (int) Cache::get("import_batch_{$batch->id}", 0),
                (int) $freshBatch->processed_rows,
            );

            $batch->update([
                'status' => 'completed',
                'processed_rows' => $finalProcessed,
                'completed_at' => now(),
            ]);

            $duration = max(microtime(true) - $startedAt, 0.001);

            Log::channel('imports')->info('Import completed', [
                'batch_id' => $batch->id,
                'type' => $batch->type,
                'duration_seconds' => round($duration, 3),
                'rows_per_second' => $finalProcessed > 0 ? round($finalProcessed / $duration, 2) : 0,
                'processed_rows' => $finalProcessed,
                'failed_rows' => $batch->fresh()->failed_rows,
            ]);

            Cache::forget('dashboard_stats');
        } catch (\Throwable $e) {
            Log::channel('imports')->error("Import EPO failed [batch #{$batch->id} - {$batch->type}]", [
                'message' => $e->getMessage(),
                'line' => $e->getFile().':'.$e->getLine(),
            ]);

            $batch->update([
                'status' => 'failed',
                'error_summary' => ['message' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            throw $e;
        } finally {
            $this->restoreModelEvents($eventDispatchers);

            if ($wasTelescopeRecording && class_exists(Telescope::class)) {
                Telescope::startRecording();
            }

            $this->restoreDatabaseSession();
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

    private function completeFromPreviousIdenticalImport(ImportBatch $batch): bool
    {
        if ($batch->force_import || ! $batch->file_hash) {
            return false;
        }

        $previous = ImportBatch::query()
            ->where('id', '<>', $batch->id)
            ->where('type', $batch->type)
            ->where('file_hash', $batch->file_hash)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if (! $previous) {
            return false;
        }

        $metadata = $batch->metadata ?? [];
        $metadata['sync_mode'] = 'file_hash_skip';
        $metadata['skipped_duplicate_of'] = $previous->id;

        $batch->update([
            'total_rows' => $previous->total_rows,
            'processed_rows' => $previous->total_rows,
            'failed_rows' => 0,
            'created_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => $previous->total_rows,
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'metadata' => $metadata,
        ]);

        Cache::put("import_batch_{$batch->id}", (int) $previous->total_rows, now()->addHour());

        Log::channel('imports')->info('Import skipped because file already synchronized', [
            'batch_id' => $batch->id,
            'type' => $batch->type,
            'duplicate_of' => $previous->id,
            'rows' => $previous->total_rows,
        ]);

        return true;
    }

    private function optimizeDatabaseSession(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ([
            'SET SESSION sql_log_bin = 0',
            'SET SESSION bulk_insert_buffer_size = 67108864',
            'SET SESSION wait_timeout = 28800',
            'SET SESSION net_read_timeout = 600',
            'SET SESSION net_write_timeout = 600',
        ] as $statement) {
            try {
                DB::statement($statement);
            } catch (\Throwable $e) {
                Log::channel('imports')->warning('MySQL import session optimization skipped', [
                    'statement' => $statement,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function restoreDatabaseSession(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach ([
            'SET SESSION sql_log_bin = 1',
        ] as $statement) {
            try {
                DB::statement($statement);
            } catch (\Throwable $e) {
                Log::channel('imports')->warning('MySQL import session restore skipped', [
                    'statement' => $statement,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function countRowsFast(string $path): int
    {
        $reader = IOFactory::createReaderForFile($path);
        $worksheets = $reader->listWorksheetInfo($path);
        $rows = (int) ($worksheets[0]['totalRows'] ?? 0);

        return max(0, $rows - 1);
    }

    /**
     * @return array<class-string, mixed>
     */
    private function disableModelEvents(): array
    {
        $models = [
            Facture::class,
            Prestation::class,
            Paiement::class,
            Client::class,
            Navire::class,
            Escale::class,
        ];
        $dispatchers = [];

        foreach ($models as $model) {
            $dispatchers[$model] = $model::getEventDispatcher();
            $model::unsetEventDispatcher();
        }

        return $dispatchers;
    }

    /**
     * @param array<class-string, mixed> $dispatchers
     */
    private function restoreModelEvents(array $dispatchers): void
    {
        foreach ($dispatchers as $model => $dispatcher) {
            if ($dispatcher instanceof \Illuminate\Contracts\Events\Dispatcher) {
                $model::setEventDispatcher($dispatcher);

                continue;
            }

            $model::unsetEventDispatcher();
        }
    }
}
