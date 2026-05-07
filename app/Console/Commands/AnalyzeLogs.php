<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AnalyzeLogs extends Command
{
    protected $signature = 'epo:analyze-logs';
    protected $description = 'Analyse les logs des imports EPO et signale les lenteurs/erreurs récurrentes.';

    public function handle(): int
    {
        $files = collect(File::glob(storage_path('logs/imports*.log')))->filter(fn ($file) => File::isFile($file));

        if ($files->isEmpty()) {
            $this->warn('Aucun fichier storage/logs/imports*.log trouvé.');
            return self::SUCCESS;
        }

        $stats = [];
        $errors = [];

        foreach ($files as $file) {
            foreach (File::lines($file) as $line) {
                $type = $this->extractValue($line, 'type');
                $batchId = $this->extractValue($line, 'batch_id') ?? $this->extractBatchId($line);
                $key = $batchId ?: ($type ?: 'unknown');

                if (str_contains($line, 'Import started')) {
                    $stats[$key]['type'] = $type ?? ($stats[$key]['type'] ?? 'unknown');
                    $stats[$key]['rows'] = (int) ($this->extractValue($line, 'rows') ?? 0);
                    $stats[$key]['started'] = $this->extractDate($line);
                }

                if (str_contains($line, 'TIMING')) {
                    $stats[$key]['type'] = $type ?? $this->extractTimingType($line) ?? ($stats[$key]['type'] ?? 'unknown');
                    $stats[$key]['duration'] = (float) ($this->extractValue($line, 'after_import') ?? $stats[$key]['duration'] ?? 0);
                }

                if (str_contains($line, 'Import completed')) {
                    $stats[$key]['type'] = $type ?? ($stats[$key]['type'] ?? 'unknown');
                    $stats[$key]['processed'] = (int) ($this->extractValue($line, 'processed_rows') ?? 0);
                    $stats[$key]['errors'] = (int) ($this->extractValue($line, 'failed_rows') ?? 0);
                    $stats[$key]['duration'] = (float) ($this->extractValue($line, 'duration_seconds') ?? $stats[$key]['duration'] ?? 0);
                }

                if (preg_match('/missing invoices|failed|error|exception|timeout|memory|Requêtes lentes/i', $line)) {
                    $errors[$type ?: 'unknown'][] = trim($line);
                }
            }
        }

        $rows = collect($stats)->map(function (array $row) {
            $processed = (int) ($row['processed'] ?? $row['rows'] ?? 0);
            $duration = (float) ($row['duration'] ?? 0);

            return [
                'Type' => $row['type'] ?? 'unknown',
                'Durée' => $duration > 0 ? $this->formatDuration($duration) : 'n/a',
                'Lignes/sec' => $duration > 0 ? round($processed / max($duration, 0.001), 2).'/s' : 'n/a',
                'Erreurs' => (int) ($row['errors'] ?? 0),
            ];
        })->values()->all();

        if ($rows === []) {
            $this->warn('Aucun timing d’import exploitable trouvé dans imports.log.');
        } else {
            $this->table(['Type', 'Durée', 'Lignes/sec', 'Erreurs'], $rows);
        }

        $this->newLine();
        $this->info('RECOMMANDATIONS');

        foreach ($rows as $row) {
            $errorsCount = (int) $row['Erreurs'];
            $speed = (float) str_replace('/s', '', (string) $row['Lignes/sec']);

            if ($row['Type'] === 'paiements' && $errorsCount > 0) {
                $this->warn("paiements : {$errorsCount} lignes en erreur, lancer php artisan epo:diagnose-paiements <fichier>.");
            }

            if ($row['Type'] === 'prestations_payees' && $speed > 0 && $speed < 80) {
                $this->warn('prestations_payees : débit faible, chunk_size attendu = 2000 et index facture_id/article unique.');
            }

            if ($row['Type'] === 'factures' && $speed > 0 && $speed < 50) {
                $this->warn('factures : débit faible, vérifier Telescope et index unique numero_facture.');
            }
        }

        foreach ($errors as $type => $lines) {
            if ($lines !== []) {
                $this->line($type.' : '.count($lines).' patterns erreur/lenteur détectés.');
            }
        }

        return self::SUCCESS;
    }

    private function extractValue(string $line, string $key): ?string
    {
        if (preg_match('/"'.preg_quote($key, '/').'"\s*:\s*"?([^",}\]]+)/', $line, $matches)) {
            return trim($matches[1], '" ');
        }

        return null;
    }

    private function extractBatchId(string $line): ?string
    {
        return preg_match('/batch #(\d+)/', $line, $matches) ? $matches[1] : null;
    }

    private function extractTimingType(string $line): ?string
    {
        return preg_match('/TIMING \[batch #\d+\] ([a-z_]+)/', $line, $matches) ? $matches[1] : null;
    }

    private function extractDate(string $line): ?string
    {
        return preg_match('/^\[([^\]]+)\]/', $line, $matches) ? $matches[1] : null;
    }

    private function formatDuration(float $seconds): string
    {
        $seconds = (int) round($seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        return trim(($hours ? "{$hours}h " : '').($minutes ? "{$minutes}m " : '')."{$remaining}s");
    }
}
