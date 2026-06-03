<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuditImportStorage extends Command
{
    /**
     * @var string
     */
    protected $signature = 'imports:audit-storage
        {--type= : Filtrer par type d\'import}';

    /**
     * @var string
     */
    protected $description = 'Liste les incohérences entre les fichiers d\'import et la base de données.';

    public function handle(): int
    {
        $type = $this->option('type');

        if ($type !== null && $type !== '' && ! in_array($type, config('imports.types', []), true)) {
            $this->error("Type d'import invalide : {$type}.");
            return self::FAILURE;
        }

        $diskName = (string) config('imports.cleanup.disk', 'local');
        $disk = Storage::disk($diskName);

        $this->info('Audit du stockage des imports en cours...');

        $batches = ImportBatch::query()
            ->when($type, fn ($query) => $query->where('type', $type))
            ->select(['id', 'type', 'stored_path', 'status', 'completed_at', 'metadata'])
            ->orderBy('id')
            ->get();

        $batchPaths = $batches
            ->pluck('stored_path')
            ->filter()
            ->values()
            ->all();

        $orphanBatches = $batches
            ->filter(fn (ImportBatch $batch) => ($batch->metadata['cleanup_status'] ?? null) !== 'cleaned'
                && $batch->status === 'completed'
                && $batch->completed_at !== null
                && ! $disk->exists($batch->stored_path))
            ->values();

        $knownPaths = collect($batchPaths)->flip();
        $orphanFiles = collect($disk->allFiles('imports'))
            ->filter(fn (string $path) => ! $knownPaths->has($path))
            ->values();

        $this->line(sprintf('Fichiers connus en base : %d', $batches->count()));
        $this->line(sprintf('Lots complets sans fichier : %d', $orphanBatches->count()));
        $this->line(sprintf('Fichiers sur disque sans lot : %d', $orphanFiles->count()));

        if ($orphanBatches->isNotEmpty()) {
            $this->newLine();
            $this->warn('Lots complets dont le fichier physique est introuvable :');
            $this->table(
                ['ID', 'Type', 'Chemin', 'Statut', 'Complété le'],
                $orphanBatches->map(fn (ImportBatch $batch) => [
                    $batch->id,
                    $batch->type,
                    $batch->stored_path,
                    $batch->status,
                    optional($batch->completed_at)->format('d/m/Y H:i'),
                ])->all()
            );
        }

        if ($orphanFiles->isNotEmpty()) {
            $this->newLine();
            $this->warn('Fichiers présents sur disque sans lot correspondant :');
            $this->table(
                ['Chemin'],
                $orphanFiles->map(fn (string $path) => [$path])->all()
            );
        }

        Log::channel('imports-cleanup')->info('Audit du stockage des imports terminé.', [
            'type' => $type,
            'batches_count' => $batches->count(),
            'missing_files' => $orphanBatches->count(),
            'orphan_files' => $orphanFiles->count(),
        ]);

        return ($orphanBatches->isNotEmpty() || $orphanFiles->isNotEmpty())
            ? self::FAILURE
            : self::SUCCESS;
    }
}
