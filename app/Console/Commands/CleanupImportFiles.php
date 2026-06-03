<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupImportFiles extends Command
{
    /**
     * @var string
     */
    protected $signature = 'imports:cleanup
        {--days= : Nombre de jours de rétention avant suppression}
        {--type= : Filtrer par type d\'import (factures, prestations, paiements)}
        {--dry-run : Simuler le nettoyage sans supprimer de fichier}
        {--force : Exécuter le nettoyage réel}';

    /**
     * @var string
     */
    protected $description = 'Nettoie les fichiers importés obsolètes tout en conservant la traçabilité en base.';

    public function handle(): int
    {
        $config = config('imports.cleanup', []);
        $enabled = (bool) ($config['enabled'] ?? true);
        $defaultDays = (int) ($config['retention_days'] ?? 30);
        $days = max(1, (int) ($this->option('days') ?: $defaultDays));
        $type = $this->option('type');
        $dryRun = (bool) $this->option('dry-run') || ! (bool) $this->option('force');
        $diskName = (string) ($config['disk'] ?? 'local');
        $disk = Storage::disk($diskName);

        if (! in_array($type, [null, '', ...config('imports.types', [])], true)) {
            $this->error("Type d'import invalide : {$type}.");
            return self::FAILURE;
        }

        if (! $enabled && ! $dryRun) {
            $this->warn("Le nettoyage automatique des imports est désactivé par configuration.");
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $summary = [
            'scanned' => 0,
            'eligible' => 0,
            'deleted' => 0,
            'dry_run' => $dryRun,
            'bytes_freed' => 0,
            'missing_files' => 0,
            'errors' => 0,
        ];

        $this->info(sprintf(
            'Nettoyage des imports %s. Rétention: %d jour(s). Type: %s. Mode: %s.',
            $enabled ? 'activé' : 'désactivé',
            $days,
            $type ?: 'tous',
            $dryRun ? 'simulation' : 'réel'
        ));

        Log::channel('imports-cleanup')->info('Démarrage du nettoyage des imports.', [
            'cutoff' => $cutoff->toDateTimeString(),
            'days' => $days,
            'type' => $type,
            'dry_run' => $dryRun,
            'disk' => $diskName,
        ]);

        ImportBatch::query()
            ->cleanupCandidates($days, $type)
            ->orderBy('id')
            ->chunkById(100, function (Collection $batches) use (&$summary, $disk, $dryRun, $diskName, $days): void {
                foreach ($batches as $batch) {
                    $summary['scanned']++;

                    if (($batch->metadata['cleanup_status'] ?? null) === 'cleaned') {
                        continue;
                    }

                    $summary['eligible']++;
                    $path = (string) $batch->stored_path;
                    $context = [
                        'batch_id' => $batch->id,
                        'type' => $batch->type,
                        'path' => $path,
                        'completed_at' => optional($batch->completed_at)->toDateTimeString(),
                        'disk' => $diskName,
                        'dry_run' => $dryRun,
                    ];

                    if ($path === '') {
                        $summary['errors']++;
                        Log::channel('imports-cleanup')->warning('Lot ignoré: chemin vide.', $context);
                        continue;
                    }

                    if (! $disk->exists($path)) {
                        $summary['missing_files']++;
                        Log::channel('imports-cleanup')->warning('Fichier source introuvable lors du nettoyage.', $context);
                        continue;
                    }

                    $bytes = $this->safeSize($diskName, $path);

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[SIMULATION] Lot #%d (%s) - %s',
                            $batch->id,
                            $batch->type,
                            $path
                        ));

                        Log::channel('imports-cleanup')->info('Simulation de suppression d\'un fichier import.', $context + [
                            'bytes' => $bytes,
                            'retention_days' => $days,
                        ]);
                        continue;
                    }

                    if (! $this->deleteWithRetry($diskName, $path, 3, 200000)) {
                        $summary['errors']++;
                        Log::channel('imports-cleanup')->error('Échec de suppression du fichier import.', $context + [
                            'bytes' => $bytes,
                        ]);
                        continue;
                    }

                    try {
                        $batch->markCleanupMetadata([
                            'cleanup_status' => 'cleaned',
                            'cleanup_mode' => 'automatic',
                            'cleanup_disk' => $diskName,
                            'cleanup_retention_days' => $days,
                            'cleanup_checked_at' => now()->toIso8601String(),
                            'archived_at' => now()->toIso8601String(),
                            'cleanup_deleted_bytes' => $bytes,
                        ]);
                    } catch (Throwable $e) {
                        $summary['errors']++;
                        Log::channel('imports-cleanup')->error('Fichier supprimé mais métadonnées impossibles à mettre à jour.', $context + [
                            'bytes' => $bytes,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }

                    $summary['deleted']++;
                    $summary['bytes_freed'] += $bytes;

                    Log::channel('imports-cleanup')->info('Fichier import supprimé avec succès.', $context + [
                        'bytes' => $bytes,
                    ]);
                }
            });

        $message = sprintf(
            'Nettoyage terminé. Scannés: %d. Éligibles: %d. Supprimés: %d. Fichiers manquants: %d. Erreurs: %d. Espace libéré: %s.',
            $summary['scanned'],
            $summary['eligible'],
            $summary['deleted'],
            $summary['missing_files'],
            $summary['errors'],
            $this->humanBytes($summary['bytes_freed'])
        );

        if ($dryRun) {
            $this->warn($message);
        } else {
            $this->info($message);
        }

        Log::channel('imports-cleanup')->info('Fin du nettoyage des imports.', $summary + [
            'message' => $message,
        ]);

        return self::SUCCESS;
    }

    private function safeSize(string $diskName, string $path): int
    {
        try {
            return (int) Storage::disk($diskName)->size($path);
        } catch (Throwable) {
            return 0;
        }
    }

    private function deleteWithRetry(string $diskName, string $path, int $attempts = 3, int $delayMicros = 200000): bool
    {
        $disk = Storage::disk($diskName);

        for ($attempt = 1; $attempt <= max(1, $attempts); $attempt++) {
            try {
                if (! $disk->exists($path)) {
                    return true;
                }

                if ($disk->delete($path)) {
                    return ! $disk->exists($path);
                }
            } catch (Throwable $e) {
                Log::channel('imports-cleanup')->warning('Tentative de suppression échouée.', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);
            }

            usleep($delayMicros);
        }

        return false;
    }

    private function humanBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return number_format($bytes / (1024 ** $power), $power === 0 ? 0 : 2, ',', ' ') . ' ' . $units[$power];
    }
}
